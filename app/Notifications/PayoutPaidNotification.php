<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PayoutPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Payout $payout) {}

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
            'title' => 'تم صرف توزيعة',
            'body' => 'تم صرف توزيعة بقيمة '.number_format((float) $this->payout->amount, 2).' ر.س إلى حسابك.',
            'type' => 'payout_paid',
        ];
    }
}
