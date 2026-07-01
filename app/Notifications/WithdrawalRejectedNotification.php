<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WithdrawalRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Withdrawal $withdrawal, public readonly string $reason) {}

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
            'title' => 'تم رفض طلب السحب',
            'body' => 'رُفض طلب سحب '.number_format((float) $this->withdrawal->amount, 2).' ر.س وأُعيد المبلغ إلى محفظتك. السبب: '.$this->reason,
            'type' => 'withdrawal_rejected',
        ];
    }
}
