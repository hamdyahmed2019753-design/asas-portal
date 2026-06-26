<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Portal\PayoutPortalService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalPayoutsTest extends TestCase
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

    private function investment(User $user, ?Contract $contract = null): Investment
    {
        return Investment::create([
            'user_id' => $user->id,
            'contract_id' => ($contract ?? Contract::create([
                'title' => 'عقد '.uniqid(), 'activity_type' => 'تجارة', 'target_amount' => 1_000_000,
                'min_amount' => 1_000, 'duration_months' => 12, 'payouts_count' => 4, 'status' => 'open',
            ]))->id,
            'amount' => 50000,
            'status' => 'approved',
        ]);
    }

    private function payout(Investment $inv, string $type, string $status, ?float $amount, string $due, ?string $paid = null): Payout
    {
        return Payout::create([
            'investment_id' => $inv->id,
            'type' => $type,
            'sequence' => $type === 'profit' ? 1 : null,
            'due_date' => $due,
            'amount' => $amount,
            'status' => $status,
            'paid_at' => $paid,
        ]);
    }

    /** A user with a known mix of payouts. */
    private function userWithPayouts(): array
    {
        $user = $this->member();
        $inv = $this->investment($user);

        $this->payout($inv, 'profit', 'paid', 1500, '2026-04-01', now()->toDateTimeString());
        $this->payout($inv, 'profit', 'paid', 1500, '2026-07-01', now()->toDateTimeString());
        $this->payout($inv, 'profit', 'due', 1000, '2026-10-01');
        $this->payout($inv, 'profit', 'scheduled', 1000, '2027-01-01');
        $this->payout($inv, 'capital', 'scheduled', 50000, '2027-01-01');

        return [$user, $inv];
    }

    private function data(User $user, array $query = []): array
    {
        return app(PayoutPortalService::class)->data($user, Request::create('/', 'GET', $query));
    }

    public function test_page_works_for_investor(): void
    {
        [$user] = $this->userWithPayouts();

        $this->actingAs($user)->get('/portal/payouts')->assertOk()->assertSee('التوزيعات');
    }

    public function test_page_works_for_member(): void
    {
        $this->actingAs($this->member())->get('/portal/payouts')->assertOk();
    }

    public function test_kpi_counts(): void
    {
        [$user] = $this->userWithPayouts();

        $kpis = $this->data($user)['kpis'];

        $this->assertSame(2, $kpis['paid']);
        $this->assertSame(1, $kpis['due']);
        $this->assertSame(2, $kpis['upcoming']); // 1 scheduled profit + 1 scheduled capital
    }

    public function test_kpi_total_paid_profits(): void
    {
        [$user] = $this->userWithPayouts();

        $this->assertSame(3000.0, $this->data($user)['kpis']['profitPaid']);
    }

    public function test_tab_paid(): void
    {
        [$user] = $this->userWithPayouts();

        $payouts = $this->data($user, ['tab' => 'paid'])['payouts'];

        $this->assertCount(2, $payouts);
        $this->assertTrue($payouts->every(fn ($p) => $p->status->value === 'paid'));
    }

    public function test_tab_due(): void
    {
        [$user] = $this->userWithPayouts();

        $payouts = $this->data($user, ['tab' => 'due'])['payouts'];

        $this->assertCount(1, $payouts);
        $this->assertSame('due', $payouts->first()->status->value);
    }

    public function test_tab_upcoming(): void
    {
        [$user] = $this->userWithPayouts();

        $payouts = $this->data($user, ['tab' => 'upcoming'])['payouts'];

        $this->assertCount(2, $payouts);
        $this->assertTrue($payouts->every(fn ($p) => $p->status->value === 'scheduled'));
    }

    public function test_filter_by_contract(): void
    {
        $user = $this->member();
        $inv1 = $this->investment($user);
        $inv2 = $this->investment($user);
        $p1 = $this->payout($inv1, 'profit', 'due', 1000, '2026-04-01');
        $p2 = $this->payout($inv2, 'profit', 'due', 1000, '2026-04-01');

        $payouts = $this->data($user, ['contract' => $inv1->contract_id])['payouts'];

        $this->assertTrue($payouts->contains($p1));
        $this->assertFalse($payouts->contains($p2));
    }

    public function test_filter_by_type(): void
    {
        [$user] = $this->userWithPayouts();

        $payouts = $this->data($user, ['type' => 'capital'])['payouts'];

        $this->assertCount(1, $payouts);
        $this->assertSame('capital', $payouts->first()->type->value);
    }

    public function test_filter_by_year(): void
    {
        [$user] = $this->userWithPayouts();

        $payouts = $this->data($user, ['year' => '2027'])['payouts'];

        $this->assertCount(2, $payouts);
        $this->assertTrue($payouts->every(fn ($p) => $p->due_date->year === 2027));
    }

    public function test_empty_state(): void
    {
        $this->actingAs($this->member())
            ->get('/portal/payouts')
            ->assertOk()
            ->assertSee('لا توجد توزيعات');
    }

    public function test_ownership_isolation(): void
    {
        [$userA] = $this->userWithPayouts();
        $foreignPayout = $userA->payouts()->first();

        $userB = $this->member();
        $invB = $this->investment($userB);
        $this->payout($invB, 'profit', 'paid', 500, '2026-04-01', now()->toDateTimeString());

        $payoutsForB = $this->data($userB)['payouts'];

        $this->assertFalse($payoutsForB->contains('id', $foreignPayout->id));
        $this->assertCount(1, $payoutsForB);
    }

    public function test_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();
        $inv = $this->investment($user);
        for ($i = 0; $i < 12; $i++) {
            $this->payout($inv, 'profit', 'paid', 100, '2026-04-01', now()->toDateTimeString());
        }

        DB::enableQueryLog();
        $this->actingAs($user)->get('/portal/payouts')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $queryCount);
    }
}
