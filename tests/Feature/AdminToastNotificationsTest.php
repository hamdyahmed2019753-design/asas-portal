<?php

namespace Tests\Feature;

use App\Enums\AdminNotificationCategory;
use App\Livewire\AdminToastNotifications;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verifies the live admin-notification path: the polling component picks up a
 * freshly-created AdminNotification and dispatches the browser event that drives
 * the in-page popup, the bell ring, and the sound.
 */
class AdminToastNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function admin(): User
    {
        $user = User::forceCreate([
            'name' => 'مدير', 'email' => uniqid('a_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('admin');

        return $user;
    }

    public function test_poller_dispatches_live_event_for_a_new_notification(): void
    {
        $admin = $this->admin();
        // Baseline in the past so the new row counts as "new".
        session(['admin_toast_last_seen_at' => now()->subMinute()->toDateTimeString()]);

        $admin->notify(new AdminNotification(
            title: 'مستثمر جديد',
            body: 'سجّل مستثمر جديد في المنصّة.',
            category: AdminNotificationCategory::User,
        ));

        Livewire::actingAs($admin)
            ->test(AdminToastNotifications::class)
            ->call('pollNew')
            ->assertDispatched('admin-notifications-new');
    }

    public function test_poller_is_silent_when_there_is_nothing_new(): void
    {
        $admin = $this->admin();
        session(['admin_toast_last_seen_at' => now()->toDateTimeString()]);

        Livewire::actingAs($admin)
            ->test(AdminToastNotifications::class)
            ->call('pollNew')
            ->assertNotDispatched('admin-notifications-new');
    }
}
