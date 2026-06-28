<?php

namespace App\Actions\ContractInterests;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\ContractInterestStatus;
use App\Enums\InvestmentStatus;
use App\Filament\Resources\InvestmentResource;
use App\Models\ContractInterest;
use App\Models\Investment;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\ContractInterestNotification;
use Illuminate\Support\Facades\DB;

/**
 * Converts a contract interest into a (pending) investment:
 *  1. Creates the investment linking the same investor + contract.
 *  2. Marks the interest as converted.
 *  3. Notifies the investor.
 * The created investment enters the normal pending → approval flow.
 *
 * Runs inside a single transaction.
 */
class ConvertContractInterest
{
    public function execute(ContractInterest $interest, float $amount): Investment
    {
        return DB::transaction(function () use ($interest, $amount): Investment {
            $investment = Investment::create([
                'user_id' => $interest->user_id,
                'contract_id' => $interest->contract_id,
                'amount' => $amount,
                'status' => InvestmentStatus::Pending->value,
            ]);

            $interest->forceFill([
                'status' => ContractInterestStatus::Converted->value,
                'converted_at' => now(),
            ])->save();

            $interest->user->notify(new ContractInterestNotification('converted', $interest->contract->title));

            NotifyAdmins::send(new AdminNotification(
                title: 'تحويل اهتمام إلى مشاركة',
                body: "تم تحويل اهتمام «{$interest->user->name}» بعقد «{$interest->contract->title}» إلى مشاركة.",
                category: AdminNotificationCategory::Interest,
                priority: AdminNotificationPriority::Medium,
                actor: $interest->user,
                target: $investment,
                url: InvestmentResource::getUrl('view', ['record' => $investment]),
                actionLabel: 'فتح المشاركة',
            ));

            return $investment;
        });
    }
}
