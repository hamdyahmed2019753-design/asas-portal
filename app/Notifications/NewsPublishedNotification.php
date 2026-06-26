<?php

namespace App\Notifications;

use App\Models\NewsUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewsPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly NewsUpdate $news) {}

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
            'category' => 'news',
            'title' => 'جديد في أساس',
            'body' => $this->news->title,
            'type' => 'news_published',
        ];
    }
}
