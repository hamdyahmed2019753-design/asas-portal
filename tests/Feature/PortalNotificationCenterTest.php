<?php

namespace Tests\Feature;

use App\Actions\Investments\ApproveInvestment;
use App\Actions\Payouts\MarkPayoutPaid;
use App\Enums\KycState;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\NewsUpdate;
use App\Models\Payout;
use App\Models\User;
use App\Notifications\InvestmentApprovedNotification;
use App\Services\Portal\ContractInterestService;
use App\Services\Portal\KycService;
use App\Services\Portal\NotificationCenterService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(?KycState $state = null): User
    {
        $user = User::forceCreate([
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
            'kyc_state' => $state?->value,
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function contract(): Contract
    {
        return Contract::create([
            'title' => 'صندوق النمو', 'activity_type' => 'تجارة', 'expected_return' => 12,
            'target_amount' => 1_000_000, 'min_amount' => 5_000, 'duration_months' => 12,
            'payouts_count' => 4, 'status' => 'open',
        ]);
    }

    private function notif(User $u, string $category, string $title, bool $read = false): DatabaseNotification
    {
        return $u->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\X',
            'data' => ['category' => $category, 'title' => $title, 'body' => 'تفاصيل'],
            'read_at' => $read ? now() : null,
        ]);
    }

    private function data(User $user, array $query = []): array
    {
        return app(NotificationCenterService::class)->data($user, Request::create('/', 'GET', $query));
    }

    // ----- Real event generation -----

    public function test_kyc_approval_generates_kyc_notification(): void
    {
        $user = $this->member(KycState::UnderReview);

        app(KycService::class)->approve($user);

        $n = $user->notifications()->first();
        $this->assertSame('kyc', $n->data['category']);
        $this->assertSame('تم التحقق من حسابك', $n->data['title']);
    }

    public function test_investment_approval_generates_investment_notification(): void
    {
        $user = $this->member();
        $investment = Investment::create([
            'user_id' => $user->id, 'contract_id' => $this->contract()->id,
            'amount' => 25000, 'status' => 'pending',
        ]);

        app(ApproveInvestment::class)->execute($investment);

        $this->assertSame('investment', $user->notifications()->first()->data['category']);
        $this->assertSame(1, $user->notifications()->where('type', InvestmentApprovedNotification::class)->count());
    }

    public function test_payout_paid_generates_payout_notification(): void
    {
        $user = $this->member();
        $inv = Investment::create([
            'user_id' => $user->id, 'contract_id' => $this->contract()->id,
            'amount' => 25000, 'status' => 'approved',
        ]);
        $payout = Payout::create([
            'investment_id' => $inv->id, 'type' => 'profit', 'sequence' => 1,
            'due_date' => '2026-04-01', 'amount' => 1500, 'status' => 'due',
        ]);

        app(MarkPayoutPaid::class)->execute($payout);

        $n = $user->notifications()->first();
        $this->assertSame('payout', $n->data['category']);
        $this->assertStringContainsString('1,500.00', $n->data['body']);
    }

    public function test_contract_interest_generates_notification(): void
    {
        $user = $this->member(KycState::Approved);

        app(ContractInterestService::class)->express($user, $this->contract());

        $this->assertSame('contract_interest', $user->notifications()->first()->data['category']);
    }

    public function test_news_published_notifies_members(): void
    {
        $member = $this->member();

        NewsUpdate::create([
            'title' => 'إطلاق ميزة جديدة', 'body' => 'تفاصيل',
            'is_published' => true, 'published_at' => now(),
        ]);

        $n = $member->notifications()->first();
        $this->assertNotNull($n);
        $this->assertSame('news', $n->data['category']);
        $this->assertSame('إطلاق ميزة جديدة', $n->data['body']);
    }

    // ----- Filtering / search -----

    public function test_filter_by_category(): void
    {
        $user = $this->member();
        $this->notif($user, 'kyc', 'تحقق');
        $this->notif($user, 'kyc', 'تحقق ٢');
        $this->notif($user, 'payout', 'توزيعة');

        $this->assertCount(2, $this->data($user, ['category' => 'kyc'])['notifications']->getCollection());
        $this->assertCount(1, $this->data($user, ['category' => 'payout'])['notifications']->getCollection());
    }

    public function test_filter_by_status_and_search(): void
    {
        $user = $this->member();
        $this->notif($user, 'kyc', 'إشعار غير مقروء');
        $this->notif($user, 'kyc', 'إشعار مقروء', read: true);

        $this->assertCount(1, $this->data($user, ['status' => 'unread'])['notifications']->getCollection());
        $this->assertCount(1, $this->data($user, ['status' => 'read'])['notifications']->getCollection());
        $this->assertCount(1, $this->data($user, ['q' => 'غير مقروء'])['notifications']->getCollection());
    }

    public function test_category_facets_count(): void
    {
        $user = $this->member();
        $this->notif($user, 'kyc', 'a');
        $this->notif($user, 'kyc', 'b');
        $this->notif($user, 'news', 'c');

        $facets = collect($this->data($user)['categories'])->keyBy('value');
        $this->assertSame(2, $facets['kyc']['count']);
        $this->assertSame(1, $facets['news']['count']);
    }

    // ----- Unread count caching -----

    public function test_unread_count_is_cached_and_busted_on_read(): void
    {
        Cache::flush();
        $user = $this->member();
        $a = $this->notif($user, 'kyc', 'a');
        $this->notif($user, 'kyc', 'b');

        $service = app(NotificationCenterService::class);
        $this->assertSame(2, $service->unreadCount($user));

        // Delete one directly: still cached at 2 until busted.
        $a->delete();
        $this->assertSame(2, $service->unreadCount($user));

        // Marking read busts the cache → recomputed.
        $service->markRead($user, $user->notifications()->first()->id);
        $this->assertSame(0, $service->unreadCount($user));
    }

    public function test_bell_badge_shows_unread_count(): void
    {
        $user = $this->member();
        $this->notif($user, 'kyc', 'a');
        $this->notif($user, 'kyc', 'b');

        $this->actingAs($user)->get('/portal/payouts')
            ->assertOk()
            ->assertSee('ip-bell__badge', false);
    }

    // ----- Security -----

    public function test_ownership_isolation(): void
    {
        $userA = $this->member();
        $foreign = $this->notif($userA, 'kyc', 'خاص بـ A');
        $userB = $this->member();
        $this->notif($userB, 'kyc', 'خاص بـ B');

        $listB = $this->data($userB)['notifications']->getCollection();
        $this->assertFalse($listB->contains('id', $foreign->id));

        app(NotificationCenterService::class)->markRead($userB, $foreign->id);
        $this->assertNull($foreign->fresh()->read_at);
    }

    // ----- Performance -----

    public function test_center_page_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 15; $i++) {
            $this->notif($user, 'kyc', "إشعار {$i}");
        }

        DB::enableQueryLog();
        $this->actingAs($user)->get('/portal/notifications')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $count);
    }
}
