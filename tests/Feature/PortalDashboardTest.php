<?php

namespace Tests\Feature;

use App\Actions\Investments\ApproveInvestment;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use App\Services\Portal\InvestorDashboardService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(): User
    {
        $user = User::forceCreate([
            'name' => 'عضو',
            'email' => uniqid('m_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function contract(): Contract
    {
        return Contract::create([
            'title' => 'عقد '.uniqid(),
            'activity_type' => 'تجارة',
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => 'open',
        ]);
    }

    /** Investor with one approved investment + payouts (1 paid, 1 due). */
    private function investorWithData(): User
    {
        $user = $this->member();

        $investment = Investment::create([
            'user_id' => $user->id,
            'contract_id' => $this->contract()->id,
            'amount' => 50000,
            'status' => 'pending',
        ]);
        app(ApproveInvestment::class)->execute($investment);

        $profits = $investment->payouts()->where('type', 'profit')->orderBy('sequence')->get();
        foreach ($profits as $i => $payout) {
            $payout->amount = 1500;
            if ($i === 0) {
                $payout->status = 'paid';
                $payout->paid_at = now();
            } elseif ($i === 1) {
                $payout->status = 'due';
            }
            $payout->save();
        }

        return $user->refresh();
    }

    public function test_dashboard_works_for_an_investor(): void
    {
        $this->actingAs($this->investorWithData())
            ->get('/portal')
            ->assertOk()
            ->assertSee('لوحتي')
            ->assertSee('إجمالي قيمة المحفظة');
    }

    public function test_dashboard_works_for_a_member(): void
    {
        $this->actingAs($this->member())->get('/portal')->assertOk();
    }

    public function test_empty_state_for_member_without_investments(): void
    {
        $this->actingAs($this->member())
            ->get('/portal')
            ->assertSee('لا توجد مشاركات بعد')
            ->assertSee('العقود الاستثمارية');
    }

    public function test_kpis_are_correct(): void
    {
        $data = app(InvestorDashboardService::class)->for($this->investorWithData());

        $this->assertSame(50000.0, $data['totalInvested']);
        $this->assertSame(1500.0, $data['profitPaid']);
        $this->assertSame(6000.0, $data['profitExpected']); // 4 profit payouts × 1500
        $this->assertSame(1, $data['investmentsCount']);
        $this->assertSame(1, $data['activeCount']);
    }

    public function test_hero_balance_is_capital_plus_realised_profit(): void
    {
        $data = app(InvestorDashboardService::class)->for($this->investorWithData());

        $this->assertSame(51500.0, $data['portfolioValue']); // 50000 + 1500
    }

    public function test_upcoming_payout_is_present_with_contract(): void
    {
        $data = app(InvestorDashboardService::class)->for($this->investorWithData());

        $this->assertNotNull($data['nextPayout']);
        $this->assertTrue($data['nextPayout']->relationLoaded('investment'));
        $this->assertNotNull($data['nextPayout']->investment->contract);
    }

    public function test_latest_investments_are_limited_to_five(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 6; $i++) {
            Investment::create([
                'user_id' => $user->id,
                'contract_id' => $this->contract()->id,
                'amount' => 1000,
                'status' => 'pending',
            ]);
        }

        $data = app(InvestorDashboardService::class)->for($user);

        $this->assertCount(5, $data['latestInvestments']);
    }

    public function test_growth_chart_has_twelve_months(): void
    {
        $data = app(InvestorDashboardService::class)->for($this->investorWithData());

        $this->assertCount(12, $data['growth']['labels']);
        $this->assertCount(12, $data['growth']['data']);
    }

    public function test_dashboard_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 4; $i++) {
            $investment = Investment::create([
                'user_id' => $user->id,
                'contract_id' => $this->contract()->id,
                'amount' => 5000,
                'status' => 'pending',
            ]);
            app(ApproveInvestment::class)->execute($investment);
        }

        DB::enableQueryLog();
        $this->actingAs($user->refresh())->get('/portal')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Regression guard: growth is a SINGLE grouped query (not a 12-month
        // loop). A reintroduced loop would push this back to ~31. Constant
        // regardless of investment count → no N+1.
        $this->assertLessThan(24, $queryCount);
    }
}
