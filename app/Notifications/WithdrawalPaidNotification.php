<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WithdrawalPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Withdrawal $withdrawal) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'payout',
            'title' => 'تم تنفيذ طلب السحب',
            'body' => 'تم تحويل '.number_format((float) $this->withdrawal->amount, 2).' ر.س إلى حسابك البنكي.',
            'type' => 'withdrawal_paid',
        ];
    }
}
