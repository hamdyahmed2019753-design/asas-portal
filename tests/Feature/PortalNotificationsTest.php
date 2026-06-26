<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Portal\NotificationCenterService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(): User
    {
        $user = User::create([
            'name' => 'مستثمر',
            'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function notify(User $user, string $title, bool $read = false): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\PortalNotice',
            'data' => ['title' => $title, 'body' => 'تفاصيل الإشعار'],
            'read_at' => $read ? now() : null,
        ]);
    }

    public function test_lists_notifications(): void
    {
        $user = $this->member();
        $this->notify($user, 'إشعار أول');

        $this->actingAs($user)
            ->get('/portal/notifications')
            ->assertOk()
            ->assertSee('الإشعارات')
            ->assertSee('إشعار أول');
    }

    public function test_unread_count(): void
    {
        $user = $this->member();
        $this->notify($user, 'غير مقروء 1');
        $this->notify($user, 'غير مقروء 2');
        $this->notify($user, 'مقروء', read: true);

        $counts = app(NotificationCenterService::class)->data($user, Request::create('/', 'GET'))['counts'];

        $this->assertSame(3, $counts['total']);
        $this->assertSame(2, $counts['unread']);
        $this->assertSame(1, $counts['read']);
    }

    public function test_mark_as_read(): void
    {
        $user = $this->member();
        $n = $this->notify($user, 'إشعار');

        $this->actingAs($user)
            ->post(route('portal.notifications.read', $n->id))
            ->assertRedirect();

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_mark_all_as_read(): void
    {
        $user = $this->member();
        $this->notify($user, 'أ');
        $this->notify($user, 'ب');

        $this->actingAs($user)
            ->post(route('portal.notifications.readAll'))
            ->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_ownership_isolation(): void
    {
        $userA = $this->member();
        $foreign = $this->notify($userA, 'خاص بـ A');

        $userB = $this->member();
        $this->notify($userB, 'خاص بـ B');

        // B's list never contains A's notification.
        $listForB = app(NotificationCenterService::class)->data($userB, Request::create('/', 'GET'))['notifications']->getCollection();
        $this->assertFalse($listForB->contains('id', $foreign->id));
        $this->assertCount(1, $listForB);

        // B cannot mark A's notification as read.
        app(NotificationCenterService::class)->markRead($userB, $foreign->id);
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_notifications_page_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 12; $i++) {
            $this->notify($user, "إشعار {$i}");
        }

        DB::enableQueryLog();
        $this->actingAs($user)->get('/portal/notifications')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $count);
    }
}
