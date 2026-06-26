<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $reason) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'kyc',
            'title' => 'تم رفض التحقق من الهوية',
            'body' => 'تم رفض التحقق من الهوية. السبب: '.$this->reason.' — يمكنك إعادة رفع المستندات.',
            'type' => 'kyc_rejected',
            'reason' => $this->reason,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تحديث حالة التحقق في أساس')
            ->greeting('مرحبًا '.($notifiable->name ?? '').'،')
            ->line('نأسف، لم نتمكن من اعتماد مستنداتك.')
            ->line('السبب: '.$this->reason)
            ->action('إعادة رفع المستندات', url('/portal/kyc/resubmit'));
    }
}
