<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Auth\Events\Login;

/**
 * Records every successful login into the user_logins audit table.
 */
class RecordUserLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $request = request();

        UserLogin::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'logged_in_at' => now(),
        ]);
    }
}
