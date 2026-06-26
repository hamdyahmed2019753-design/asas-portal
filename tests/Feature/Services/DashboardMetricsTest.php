<?php

namespace Tests\Feature\Services;

use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Dashboard\DashboardMetrics;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function metrics(): DashboardMetrics
    {
        return app(DashboardMetrics::class);
    }

    private function user(string $role = 'investor'): User
    {
        $user = User::create([
            'name' => 'User',
            'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function contract(string $status = 'open'): Contract
    {
        return Contract::create([
            'title' => 'عقد',
            'activity_type' => 'تجارة',
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => $status,
        ]);
    }

    private function investment(User $user, float $amount, string $status = 'approved'): Investment
    {
        return Investment::create([
            'user_id' => $user->id,
            'contract_id' => $this->contract()->id,
            'amount' => $amount,
            'status' => $status,
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2027, 1, 1),
        ]);
    }

    public function test_total_investors_counts_only_investor_role(): void
    {
        $this->user('investor');
        $this->user('investor');
        $this->user('member');

        $this->assertSame(2, $this->metrics()->totalInvestors());
    }

    public function test_total_investments_sums_only_approved(): void
    {
        $user = $this->user();
        $this->investment($user, 5000, 'approved');
        $this->investment($user, 3000, 'approved');
        $this->investment($user, 9999, 'pending');

        $this->assertSame(8000.0, $this->metrics()->totalInvestments());
    }

    public function test_pending_investments_count(): void
    {
        $user = $this->user();
        $this->investment($user, 1000, 'pending');
        $this->investment($user, 2000, 'pending');
        $this->investment($user, 3000, 'approved');

        $this->assertSame(2, $this->metrics()->pendingInvestments());
    }

    public function test_due_payouts_count(): void
    {
        $user = $this->user();
        $investment = $this->investment($user, 5000);

        foreach (['scheduled', 'due', 'due', 'paid'] as $i => $status) {
            Payout::create([
                'investment_id' => $investment->id,
                'type' => 'profit',
                'sequence' => $i + 1,
                'due_date' => Carbon::create(2026, 4, 1),
                'amount' => 100,
                'status' => $status,
            ]);
        }

        $this->assertSame(2, $this->metrics()->duePayouts());
    }

    public function test_open_and_running_contracts_counts(): void
    {
        $this->contract('open');
        $this->contract('open');
        $this->contract('running');
        $this->contract('closed');

        $this->assertSame(2, $this->metrics()->openContracts());
        $this->assertSame(1, $this->metrics()->runningContracts());
    }

    public function test_payout_status_distribution_has_fixed_order(): void
    {
        $user = $this->user();
        $investment = $this->investment($user, 5000);

        foreach (['scheduled', 'scheduled', 'due', 'paid'] as $i => $status) {
            Payout::create([
                'investment_id' => $investment->id,
                'type' => 'profit',
                'sequence' => $i + 1,
                'due_date' => Carbon::create(2026, 4, 1),
                'amount' => 100,
                'status' => $status,
            ]);
        }

        $dist = $this->metrics()->payoutStatusDistribution();

        $this->assertCount(3, $dist['labels']);
        $this->assertSame([2, 1, 1], $dist['data']); // scheduled, due, paid
    }

    public function test_contract_status_distribution_has_five_buckets(): void
    {
        $this->contract('upcoming');
        $this->contract('open');
        $this->contract('open');
        $this->contract('running');

        $dist = $this->metrics()->contractStatusDistribution();

        $this->assertCount(5, $dist['data']);
        $this->assertSame([1, 2, 1, 0, 0], $dist['data']); // upcoming, open, running, closed, finished
    }

    public function test_latest_investments_respects_limit_and_order(): void
    {
        $user = $this->user();
        for ($i = 0; $i < 7; $i++) {
            $this->investment($user, 1000 + $i);
        }

        $latest = $this->metrics()->latestInvestments(5);

        $this->assertCount(5, $latest);
        $this->assertTrue($latest->first()->created_at->gte($latest->last()->created_at));
    }

    public function test_growth_series_return_six_buckets(): void
    {
        $user = $this->user();
        $this->investment($user, 5000, 'approved');

        $growth = $this->metrics()->investmentGrowth();

        $this->assertCount(6, $growth['labels']);
        $this->assertCount(6, $growth['data']);
        $this->assertSame(5000.0, end($growth['data'])); // cumulative total at latest bucket
    }
}
