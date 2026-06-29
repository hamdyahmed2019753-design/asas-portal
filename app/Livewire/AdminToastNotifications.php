<?php

namespace App\Livewire;

use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

/**
 * Lightweight poller injected globally on the admin panel (Filament body-end
 * render hook). Every ~12s it queries the signed-in admin's AdminNotification
 * rows created since the last poll and:
 *   1. Dispatches one Filament toast each (category color + icon + priority +
 *      relative time + View deep-link, auto-close 6s).
 *   2. Dispatches a single browser event `admin-notifications-new` carrying the
 *      new notifications, which a tiny Alpine handler turns into a sound, a
 *      one-shot bell animation, and native browser notifications.
 *
 * De-duplication: the "last seen" timestamp is persisted in the session and
 * advanced to the newest notification seen, so each notification surfaces
 * exactly once per browser session. Each admin runs their own scoped poller.
 */
class AdminToastNotifications extends Component
{
    public string $lastSeenAt;

    public function mount(): void
    {
        $this->lastSeenAt = session(
            'admin_toast_last_seen_at',
            now()->toDateTimeString(),
        );
    }

    /**
     * Invoked by wire:poll. Sends toasts + a browser event for every
     * notification created after the last poll, then advances the baseline.
     */
    public function pollNew(): void
    {
        $admin = auth()->user();

        if (! $admin instanceof User || ! $admin->hasRole('admin')) {
            return;
        }

        $since = Carbon::parse($this->lastSeenAt);

        $new = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $admin->id)
            ->where('type', AdminNotification::class)
            ->where('created_at', '>', $since)
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        if ($new->isEmpty()) {
            return;
        }

        // Payloads handed to the browser (sound / bell animation / native notif).
        $browserPayload = [];

        foreach ($new as $row) {
            $category = AdminNotificationCategory::tryFrom((string) ($row->data['category'] ?? ''));
            $priority = AdminNotificationPriority::tryFrom((string) ($row->data['priority'] ?? ''));

            // Enriched toast body: priority marker + relative time alongside the
            // original message (Filament toast body is plain text, so the
            // "priority badge" + relative time render as text).
            $body = (string) ($row->data['body'] ?? '');
            if ($priority !== null) {
                $body = "【{$priority->label()}】 {$body}";
            }
            $body .= '  ·  '.$row->created_at->diffForHumans();

            $toast = Notification::make()
                ->title($row->data['title'] ?? '')
                ->body($body)
                ->icon($category?->icon() ?? 'heroicon-o-bell')
                ->color($category?->color() ?? 'primary')
                ->iconColor($category?->color() ?? 'primary')
                ->duration(6000); // auto-close after 6 seconds

            if (! empty($row->data['url'])) {
                $toast->actions([
                    Action::make('view')
                        ->label('عرض')
                        ->url($row->data['url'], true)
                        ->button(),
                ]);
            }

            $toast->send();

            $browserPayload[] = [
                'id' => $row->id,
                'title' => $row->data['title'] ?? '',
                'body' => $row->data['body'] ?? '',
                'url' => $row->data['url'] ?? '',
            ];
        }

        // One browser event for the whole batch — the Alpine handler rings the
        // bell once, plays one sound per notification (staggered), and fires one
        // native browser notification per notification.
        $this->dispatch('admin-notifications-new', notifications: $browserPayload);

        $this->lastSeenAt = $new->last()->created_at->toDateTimeString();
        session(['admin_toast_last_seen_at' => $this->lastSeenAt]);
    }

    public function render(): string
    {
        return view('livewire.admin-toast-notifications')->render();
    }
}
