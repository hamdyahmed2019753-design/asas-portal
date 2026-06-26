<?php

namespace App\Notifications;

use App\Models\Investment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvestmentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Investment $investment,
        public readonly ?string $reason = null,
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
        $body = 'نأسف، لم يتم اعتماد مشاركتك في عقد «'.($this->investment->contract->title ?? '').'».';
        if ($this->reason) {
            $body .= ' السبب: '.$this->reason;
        }

        return [
            'category' => 'investment',
            'title' => 'تم رفض مشاركتك',
            'body' => $body,
            'type' => 'investment_rejected',
        ];
    }
}
