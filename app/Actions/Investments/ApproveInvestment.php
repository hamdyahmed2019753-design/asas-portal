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
use App\Notifications\InvestmentApprovedNotification;
use App\Services\PayoutScheduleGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Approves a pending investment:
 *  1. Guards against double-processing (only pending investments may be approved).
 *  2. Sets start_date (today), end_date (today + duration), approved_at and status.
 *  3. Generates the payout schedule (profit rows + capital row).
 *  4. Grants the `investor` role to the user if not already held.
 *
 * Runs inside a single database transaction.
 */
class ApproveInvestment
{
    public function __construct(
        private readonly PayoutScheduleGenerator $generator,
    ) {}

    public function execute(Investment $investment, ?string $paymentProofPath = null): Investment
    {
        // Approvable from a submitted-payment subscription or a legacy pending
        // investment (admin-created / interest-converted).
        if (! in_array($investment->status, [InvestmentStatus::PaymentSubmitted, InvestmentStatus::Pending], true)) {
            throw InvestmentAlreadyProcessedException::forInvestment($investment->id);
        }

        return DB::transaction(function () use ($investment, $paymentProofPath): Investment {
            $today = now()->startOfDay();

            $investment->forceFill([
                'status' => InvestmentStatus::Approved,
                'start_date' => $today,
                'end_date' => $today->copy()->addMonths((int) $investment->contract->duration_months),
                'approved_at' => now(),
                'payment_confirmed_at' => now(),
                'payment_proof_path' => $paymentProofPath ?? $investment->payment_proof_path,
            ])->save();

            // Reload so casts (start_date as Carbon date) are clean for the generator.
            $investment->refresh();

            $this->generator->generate($investment);

            $user = $investment->user;
            if (! $user->hasRole('investor')) {
                $user->assignRole('investor');
            }

            $investment->loadMissing('contract');
            $user->notify(new InvestmentApprovedNotification($investment));

            $contractTitle = $investment->contract?->title ?? '—';

            NotifyAdmins::send(new AdminNotification(
                title: 'اعتماد مشاركة استثمارية',
                body: "تم اعتماد مشاركة «{$user->name}» في عقد «{$contractTitle}».",
                category: AdminNotificationCategory::Investment,
                priority: AdminNotificationPriority::Medium,
                actor: $user,
                target: $investment,
                url: InvestmentResource::getUrl('index'),
                actionLabel: 'فتح المشاركة',
            ));

            return $investment;
        });
    }
}
