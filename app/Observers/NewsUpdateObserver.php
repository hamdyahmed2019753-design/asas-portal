<?php

namespace App\Observers;

use App\Models\NewsUpdate;
use App\Models\User;
use App\Notifications\NewsPublishedNotification;
use Illuminate\Support\Facades\Notification;

class NewsUpdateObserver
{
    /**
     * When a news item becomes published, notify every investor/member once.
     */
    public function saved(NewsUpdate $news): void
    {
        $becamePublished = $news->is_published
            && ($news->wasRecentlyCreated || $news->wasChanged('is_published'));

        if (! $becamePublished) {
            return;
        }

        $recipients = User::role(['member', 'investor'])->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new NewsPublishedNotification($news));
        }
    }
}
