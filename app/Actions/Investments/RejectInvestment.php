<?php

namespace App\Actions\Investments;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\InvestmentStatus;
use App\Exceptions\InvestmentAlreadyProcessedException;
use App\Filament\Resources\InvestmentResource;
use App\Models\Investment;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\InvestmentRejectedNotification;
use Illuminate\Support\Facades\DB;

/**
 * Rejects a pending investment:
 *  - Guards against double-processing (only pending may be rejected).
 *  - Sets status = rejected, rejection_reason and rejected_at.
 *
 * Runs inside a single database transaction. No payouts are generated and no
 * role is granted.
 */
class RejectInvestment
{
    public function execute(Investment $investment, ?string $reason = null): Investment
    {
        if ($investment->status !== InvestmentStatus::Pending) {
            throw InvestmentAlreadyProcessedException::forInvestment($investment->id);
        }

        return DB::transaction(function () use ($investment, $reason): Investment {
            $investment->forceFill([
                'status' => InvestmentStatus::Rejected,
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ])->save();

            $investment->loadMissing('contract');
            $investment->user->notify(new InvestmentRejectedNotification($investment, $reason));

            $contractTitle = $investment->contract?->title ?? '—';

            NotifyAdmins::send(new AdminNotification(
                title: 'رفض مشاركة استثمارية',
                body: "تم رفض مشاركة «{$investment->user->name}» في عقد «{$contractTitle}».",
                category: AdminNotificationCategory::Investment,
                priority: AdminNotificationPriority::High,
                actor: $investment->user,
                target: $investment,
                url: InvestmentResource::getUrl('index'),
                actionLabel: 'فتح المشاركة',
            ));

            return $investment;
        });
    }
}
