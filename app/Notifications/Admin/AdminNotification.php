<?php

namespace App\Notifications\Admin;

use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Database notification routed to administrators and rendered by Filament's
 * built-in notification bell (databaseNotifications) as well as the
 * AdminNotificationResource management center.
 *
 * The icon and color are NEVER hardcoded at call sites — they resolve from the
 * AdminNotificationCategory. The payload is a flat JSON array so it is directly
 * compatible with future real-time broadcasting (Laravel Reverb): adding
 * 'broadcast' to via() and a toBroadcast() that reads the same keys is a
 * drop-in change with no structural migration.
 */
class AdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly AdminNotificationCategory $category = AdminNotificationCategory::System,
        public readonly AdminNotificationPriority $priority = AdminNotificationPriority::Medium,
        public readonly ?Model $actor = null,
        public readonly ?Model $target = null,
        public readonly ?string $url = null,
        public readonly ?string $actionLabel = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Flat, broadcast-ready payload. Every key is JSON-safe; the actor name is
     * snapshotted at creation time so rendering never triggers an extra query.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $data = [
            // Without 'format' => 'filament', Filament's database-notifications
            // widget (bell / unread counter / modal) filters this row out, so the
            // admin bell would never reflect it. The extra keys below (category,
            // priority, actor_*, target_*) are ignored by Filament's renderer.
            'format' => 'filament',
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->category->icon(),
            'iconColor' => $this->category->color(),
            'color' => $this->category->color(),
            'category' => $this->category->value,
            'priority' => $this->priority->value,
            'priorityLabel' => $this->priority->label(),
            'actor_id' => $this->actor?->getKey(),
            'actor_name' => $this->actor?->getAttribute('name'),
            'actor_type' => $this->actor !== null ? get_class($this->actor) : null,
            'target_type' => $this->target !== null ? get_class($this->target) : null,
            'target_id' => $this->target?->getKey(),
        ];

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }

        if ($this->actionLabel !== null && $this->url !== null) {
            $data['actions'] = [
                [
                    'name' => 'view',
                    'label' => $this->actionLabel,
                    'url' => $this->url,
                    'shouldOpenInNewTab' => false,
                ],
            ];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
