<?php

namespace Tests\Feature\Actions;

use App\Actions\Investments\RejectInvestment;
use App\Enums\InvestmentStatus;
use App\Exceptions\InvestmentAlreadyProcessedException;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RejectInvestmentTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingInvestment(): Investment
    {
        $user = User::forceCreate([
            'name' => 'Member',
            'email' => uniqid('member_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);

        $contract = Contract::create([
            'title' => 'عقد',
            'activity_type' => 'تجارة',
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => 'open',
        ]);

        return Investment::create([
            'user_id' => $user->id,
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'pending',
        ]);
    }

    public function test_it_rejects_a_pending_investment_with_reason(): void
    {
        $investment = $this->makePendingInvestment();

        app(RejectInvestment::class)->execute($investment, 'المبلغ غير مكتمل');

        $investment->refresh();
        $this->assertSame(InvestmentStatus::Rejected, $investment->status);
        $this->assertSame('المبلغ غير مكتمل', $investment->rejection_reason);
        $this->assertNotNull($investment->rejected_at);
    }

    public function test_it_does_not_generate_payouts_on_rejection(): void
    {
        $investment = $this->makePendingInvestment();

        app(RejectInvestment::class)->execute($investment, 'سبب');

        $this->assertSame(0, $investment->payouts()->count());
    }

    public function test_it_prevents_rejecting_an_already_processed_investment(): void
    {
        $investment = $this->makePendingInvestment();
        $action = app(RejectInvestment::class);

        $action->execute($investment, 'مرة');

        $this->expectException(InvestmentAlreadyProcessedException::class);
        $action->execute($investment->fresh(), 'مرة ثانية');
    }
}
