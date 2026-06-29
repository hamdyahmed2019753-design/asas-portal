<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Portal\PortfolioService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalPortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Carbon::setTestNow('2026-06-20');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function member(): User
    {
        $user = User::forceCreate([
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function contract(string $title, float $return): Contract
    {
        return Contract::create([
            'title' => $title, 'activity_type' => 'تجارة', 'expected_return' => $return,
            'target_amount' => 1_000_000, 'min_amount' => 1_000, 'duration_months' => 12,
            'payouts_count' => 4, 'status' => 'open',
        ]);
    }

    private function investment(User $user, Contract $contract, float $amount): Investment
    {
        return Investment::create([
            'user_id' => $user->id, 'contract_id' => $contract->id,
            'amount' => $amount, 'status' => 'approved',
        ]);
    }

    private function payout(Investment $inv, string $status, float $amount, string $due, ?string $paid = null): void
    {
        Payout::create([
            'investment_id' => $inv->id, 'type' => 'profit', 'sequence' => 1,
            'due_date' => $due, 'amount' => $amount, 'status' => $status, 'paid_at' => $paid,
        ]);
    }

    /** A user with a known two-contract portfolio. */
    private function portfolioUser(): User
    {
        $user = $this->member();
        $a = $this->investment($user, $this->contract('عقد أ', 10), 50000);
        $b = $this->investment($user, $this->contract('عقد ب', 20), 50000);

        $this->payout($a, 'paid', 1500, '2026-04-01', '2026-04-01');
        $this->payout($a, 'due', 1500, '2026-10-01');
        $this->payout($b, 'paid', 2500, '2026-05-01', '2026-05-01');
        $this->payout($b, 'paid', 1000, '2026-06-01', '2026-06-01');

        return $user;
    }

    private function data(User $user): array
    {
        return app(PortfolioService::class)->data($user);
    }

    public function test_page_renders_for_member(): void
    {
        $this->actingAs($this->member())->get('/portal/portfolio')->assertOk()->assertSee('محفظتي');
    }

    public function test_kpis(): void
    {
        $kpis = $this->data($this->portfolioUser())['kpis'];

        $this->assertSame(100000.0, $kpis['totalCapital']);
        $this->assertSame(5000.0, $kpis['realizedProfit']);   // 1500 + 2500 + 1000
        $this->assertSame(6500.0, $kpis['expectedProfit']);   // all profit payouts
        $this->assertSame(2, $kpis['activeCount']);
        $this->assertSame(15.0, $kpis['averageReturn']);      // capital-weighted (10+20)/2
        $this->assertSame(105000.0, $kpis['portfolioValue']); // capital + realized
    }

    public function test_allocation_chart(): void
    {
        $allocation = $this->data($this->portfolioUser())['allocation'];

        $this->assertEqualsCanonicalizing(['عقد أ', 'عقد ب'], $allocation['labels']);
        $this->assertSame(100000.0, array_sum($allocation['data']));
        $this->assertEqualsCanonicalizing([50.0, 50.0], $allocation['percentages']);
    }

    public function test_performance_chart_is_monthly_realized_profit(): void
    {
        $performance = $this->data($this->portfolioUser())['performance'];

        $this->assertSame(['2026-04', '2026-05', '2026-06'], $performance['labels']);
        $this->assertSame([1500.0, 2500.0, 1000.0], $performance['data']);
        $this->assertSame(5000.0, array_sum($performance['data'])); // equals realized profit
    }

    public function test_empty_state(): void
    {
        $this->actingAs($this->member())
            ->get('/portal/portfolio')
            ->assertOk()
            ->assertSee('لا توجد مشاركات في محفظتك');

        $data = $this->data($this->member());
        $this->assertFalse($data['hasInvestments']);
        $this->assertSame(0.0, $data['kpis']['totalCapital']);
        $this->assertSame(0.0, $data['kpis']['averageReturn']);
    }

    public function test_ownership_isolation(): void
    {
        $userA = $this->portfolioUser();
        $userB = $this->member();
        $this->investment($userB, $this->contract('عقد ب-خاص', 8), 7000);

        $dataB = $this->data($userB);

        $this->assertSame(7000.0, $dataB['kpis']['totalCapital']);
        $this->assertSame(1, $dataB['kpis']['activeCount']);
        $this->assertEqualsCanonicalizing(['عقد ب-خاص'], $dataB['allocation']['labels']);
        // None of A's investments leak into B's portfolio.
        $aIds = $userA->investments()->pluck('id')->all();
        $this->assertEmpty(array_intersect($aIds, $dataB['investments']->pluck('id')->all()));
    }

    public function test_performance_uses_single_aggregate_query(): void
    {
        $user = $this->portfolioUser();

        DB::enableQueryLog();
        app(PortfolioService::class)->data($user);
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        // Exactly one query references a GROUP BY on the month expression.
        $grouped = collect($log)->filter(fn ($q) => str_contains($q['query'], 'group by'))->count();
        $this->assertSame(1, $grouped);
    }

    public function test_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 8; $i++) {
            $inv = $this->investment($user, $this->contract("عقد {$i}", 10 + $i), 10000);
            $this->payout($inv, 'paid', 500, '2026-05-01', '2026-05-01');
        }

        DB::enableQueryLog();
        $this->actingAs($user)->get('/portal/portfolio')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $count);
    }
}
