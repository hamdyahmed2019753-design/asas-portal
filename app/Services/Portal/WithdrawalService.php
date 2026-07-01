<?php

namespace App\Services\Portal;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\WalletTransactionReason;
use App\Enums\WithdrawalStatus;
use App\Filament\Resources\WithdrawalResource;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\WithdrawalPaidNotification;
use App\Notifications\WithdrawalRejectedNotification;

/**
 * The wallet-withdrawal workflow. Requesting a withdrawal immediately DEBITS the
 * wallet (the funds are held); the admin then transfers to the bank, attaches a
 * receipt and marks it paid — or rejects it, which REFUNDS the wallet. Bank
 * details are snapshotted at request time so a later profile edit can't alter
 * the record.
 */
class WithdrawalService
{
    public function __construct(private readonly WalletService $wallet) {}

    public function request(User $user, float $amount): Withdrawal
    {
        $withdrawal = $user->withdrawals()->create([
            'amount' => round($amount, 2),
            'status' => WithdrawalStatus::Pending->value,
            'bank_name' => $user->bank_name,
            'bank_account_name' => $user->bank_account_name,
            'bank_iban' => $user->bank_iban,
        ]);

        // Hold the funds now; refunded if the request is rejected.
        $this->wallet->debit($user, $amount, WalletTransactionReason::Withdrawal, $withdrawal, 'طلب سحب #'.$withdrawal->id);

        NotifyAdmins::send(new AdminNotification(
            title: 'طلب سحب جديد',
            body: "طلب «{$user->name}» سحب ".money($amount).' إلى حسابه البنكي.',
            category: AdminNotificationCategory::Payout,
            priority: AdminNotificationPriority::High,
            actor: $user,
            target: $withdrawal,
            url: WithdrawalResource::getUrl('index'),
            actionLabel: 'مراجعة الطلب',
        ));

        return $withdrawal;
    }

    public function markPaid(Withdrawal $withdrawal, ?string $receiptPath = null): void
    {
        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            return;
        }

        $withdrawal->forceFill([
            'status' => WithdrawalStatus::Paid->value,
            'receipt_path' => $receiptPath ?? $withdrawal->receipt_path,
            'processed_at' => now(),
        ])->save();

        $withdrawal->user->notify(new WithdrawalPaidNotification($withdrawal));
    }

    public function reject(Withdrawal $withdrawal, string $reason): void
    {
        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            return;
        }

        $withdrawal->forceFill([
            'status' => WithdrawalStatus::Rejected->value,
            'rejection_reason' => $reason,
            'processed_at' => now(),
        ])->save();

        // Return the held funds to the wallet.
        $this->wallet->credit($withdrawal->user, (float) $withdrawal->amount, WalletTransactionReason::WithdrawalRefund, $withdrawal, 'إلغاء سحب #'.$withdrawal->id);

        $withdrawal->user->notify(new WithdrawalRejectedNotification($withdrawal, $reason));
    }
}
