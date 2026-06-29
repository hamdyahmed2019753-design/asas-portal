<?php

namespace App\Actions\Payouts;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Exceptions\PayoutAlreadyPaidException;
use App\Exceptions\PayoutAmountMissingException;
use App\Filament\Resources\PayoutResource;
use App\Models\Payout;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\PayoutPaidNotification;

/**
 * Marks a payout as paid (a manual confirmation — no real money movement).
 *
 * Validation:
 *  - A payout already marked paid cannot be paid again.
 *  - A PROFIT payout with no amount set cannot be paid (amount is manual).
 */
class MarkPayoutPaid
{
    public function execute(Payout $payout): Payout
    {
        if ($payout->status === PayoutStatus::Paid) {
            throw PayoutAlreadyPaidException::forPayout($payout->id);
        }

        if ($payout->type === PayoutType::Profit && $payout->amount === null) {
            throw PayoutAmountMissingException::forPayout($payout->id);
        }

        $payout->forceFill([
            'status' => PayoutStatus::Paid,
            'paid_at' => now(),
        ])->save();

        $payout->loadMissing('investment.user');
        $payout->investment?->user?->notify(new PayoutPaidNotification($payout));

        $investorName = $payout->investment?->user?->name ?? '—';

        NotifyAdmins::send(new AdminNotification(
            title: 'صرف توزيعة',
            body: "تم صرف توزيعة المستثمر «{$investorName}».",
            category: AdminNotificationCategory::Payout,
            priority: AdminNotificationPriority::Medium,
            actor: $payout->investment?->user,
            target: $payout,
            url: PayoutResource::getUrl('index'),
            actionLabel: 'فتح التوزيعة',
        ));

        return $payout;
    }
}
