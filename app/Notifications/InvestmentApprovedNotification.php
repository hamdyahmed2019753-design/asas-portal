<?php

namespace App\Notifications;

use App\Models\Investment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvestmentApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Investment $investment) {}

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
            'category' => 'investment',
            'title' => 'تم اعتماد مشاركتك',
            'body' => 'تم اعتماد مشاركتك الاستثمارية في عقد «'.($this->investment->contract->title ?? '').'».',
            'type' => 'investment_approved',
        ];
    }
}
