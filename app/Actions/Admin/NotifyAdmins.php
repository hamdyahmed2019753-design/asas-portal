<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Broadcasts an AdminNotification to every user holding the `admin` role.
 *
 * The admin id list is cached briefly so repeated events (e.g. a batch of due
 * payouts) do not re-query the roles table on every iteration. Because
 * AdminNotification implements ShouldQueue, delivery is handed to Horizon
 * asynchronously and stays off the request path.
 */
class NotifyAdmins
{
    private const CACHE_TTL = 300; // seconds

    public static function send(AdminNotification $notification): void
    {
        $admins = Cache::remember(
            'admins.notify.ids',
            now()->addSeconds(self::CACHE_TTL),
            // whereHas (not the Spatie role() scope) so a missing `admin` role
            // yields no recipients instead of throwing RoleDoesNotExist — keeps
            // business actions (approvals, payouts, registration) crash-safe.
            fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get(['id']),
        );

        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }
}
