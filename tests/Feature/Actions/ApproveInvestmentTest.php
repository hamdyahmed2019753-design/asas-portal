<?php

namespace Tests\Feature\Actions;

use App\Actions\Investments\ApproveInvestment;
use App\Enums\InvestmentStatus;
use App\Exceptions\InvestmentAlreadyProcessedException;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproveInvestmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function makePendingInvestment(int $duration = 12, int $payoutsCount = 4): Investment
    {
        $user = User::forceCreate([
            'name' => 'Member',
            'email' => uniqid('member_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('member');

        $contract = Contract::create([
            'title' => 'عقد',
            'activity_type' => 'تجارة',
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'duration_months' => $duration,
            'payouts_count' => $payoutsCount,
            'status' => 'open',
        ]);

        return Investment::create([
            'user_id' => $user->id,
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'pending',
        ]);
    }

    public function test_it_approves_investment_and_sets_dates(): void
    {
        $investment = $this->makePendingInvestment(12);

        app(ApproveInvestment::class)->execute($investment);

        $investment->refresh();
        $this->assertSame(InvestmentStatus::Approved, $investment->status);
        $this->assertSame(now()->startOfDay()->toDateString(), $investment->start_date->toDateString());
        $this->assertSame(now()->startOfDay()->addMonths(12)->toDateString(), $investment->end_date->toDateString());
        $this->assertNotNull($investment->approved_at);
    }

    public function test_it_generates_payout_schedule_on_approval(): void
    {
        $investment = $this->makePendingInvestment(12, 4);

        app(ApproveInvestment::class)->execute($investment);

        $this->assertSame(5, $investment->payouts()->count());       // 4 profit + 1 capital
        $this->assertSame(4, $investment->payouts()->profit()->count());
        $this->assertSame(1, $investment->payouts()->capital()->count());
    }

    public function test_it_grants_investor_role_to_user(): void
    {
        $investment = $this->makePendingInvestment();

        app(ApproveInvestment::class)->execute($investment);

        $this->assertTrue($investment->user->fresh()->hasRole('investor'));
    }

    public function test_it_prevents_duplicate_approval(): void
    {
        $investment = $this->makePendingInvestment();
        $action = app(ApproveInvestment::class);

        $action->execute($investment);

        $this->expectException(InvestmentAlreadyProcessedException::class);
        $action->execute($investment->fresh());
    }

    public function test_duplicate_approval_does_not_create_extra_payouts(): void
    {
        $investment = $this->makePendingInvestment();
        $action = app(ApproveInvestment::class);

        $action->execute($investment);

        try {
            $action->execute($investment->fresh());
        } catch (InvestmentAlreadyProcessedException) {
            // expected
        }

        $this->assertSame(5, $investment->payouts()->count());
    }
}
