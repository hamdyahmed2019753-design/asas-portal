<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            'title' => 'تم التحقق من حسابك',
            'body' => 'تم اعتماد التحقق من الهوية بنجاح. يمكنك الآن المشاركة في الفرص الاستثمارية.',
            'type' => 'kyc_approved',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم التحقق من حسابك في أساس')
            ->greeting('مرحبًا '.($notifiable->name ?? '').'،')
            ->line('تم اعتماد مستنداتك بنجاح، وأصبح بإمكانك المشاركة في الفرص الاستثمارية المتاحة.')
            ->action('الذهاب إلى المحفظة', url('/portal'));
    }
}
