<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Database notification for every step of the contract-interest lifecycle:
 * submitted · contacted · converted · rejected.
 */
class ContractInterestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $event,
        public readonly string $contractTitle,
    ) {}

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
        [$title, $body] = match ($this->event) {
            'submitted' => ['تم استلام طلب اهتمامك', 'استلمنا طلب اهتمامك بعقد «'.$this->contractTitle.'»، وسنتواصل معك قريبًا.'],
            'contacted' => ['تم التواصل بشأن طلبك', 'تم التواصل معك بخصوص اهتمامك بعقد «'.$this->contractTitle.'».'],
            'converted' => ['تم تحويل اهتمامك إلى مشاركة', 'تم تحويل اهتمامك بعقد «'.$this->contractTitle.'» إلى مشاركة استثمارية.'],
            'rejected' => ['تم إغلاق طلب اهتمامك', 'نأسف، تم إغلاق طلب اهتمامك بعقد «'.$this->contractTitle.'».'],
            default => ['تحديث على طلب اهتمامك', 'تم تحديث حالة طلب اهتمامك بعقد «'.$this->contractTitle.'».'],
        };

        return [
            'category' => 'contract_interest',
            'title' => $title,
            'body' => $body,
            'type' => 'contract_interest_'.$this->event,
        ];
    }
}
