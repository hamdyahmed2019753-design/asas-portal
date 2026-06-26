<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class KycSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            'category' => 'kyc',
            'title' => 'تم استلام مستندات التحقق',
            'body' => 'تم استلام مستندات التحقق من الهوية، وهي الآن قيد المراجعة.',
            'type' => 'kyc_submitted',
        ];
    }
}
