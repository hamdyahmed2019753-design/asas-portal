<?php

namespace Tests\Unit\Services;

use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use App\Services\InvestorBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvestorBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Investor',
            'email' => uniqid('inv_').'@test.local',
            'password' => 'secret123',
        ]);
    }

    private function makeContract(): Contract
    {
        return Contract::create([
            'title' => 'عقد',
            'activity_type' => 'تجارة',
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => 'open',
        ]);
    }

    private function makeInvestment(User $user, float $amount, string $status = 'approved'): Investment
    {
        return Investment::create([
            'user_id' => $user->id,
            'contract_id' => $this->makeContract()->id,
            'amount' => $amount,
            'status' => $status,
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2027, 1, 1),
        ]);
    }

    private function makePayout(Investment $investment, string $type, ?float $amount, string $status, Carbon $due): Payout
    {
        return Payout::create([
            'investment_id' => $investment->id,
            'type' => $type,
            'sequence' => $type === 'profit' ? 1 : null,
            'due_date' => $due,
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    public function test_total_invested_sums_only_approved_investments(): void
    {
        $user = $this->makeUser();
        $this->makeInvestment($user, 5000, 'approved');
        $this->makeInvestment($user, 3000, 'approved');
        $this->makeInvestment($user, 9999, 'pending'); // excluded

        $balance = app(InvestorBalance::class)->for($user);

        $this->assertSame(8000.0, $balance->totalInvested);
    }

    public function test_total_profit_paid_counts_only_paid_profit_payouts(): void
    {
        $user = $this->makeUser();
        $investment = $this->makeInvestment($user, 5000);

        $this->makePayout($investment, 'profit', 200, 'paid', Carbon::create(2026, 4, 1));
        $this->makePayout($investment, 'profit', 250, 'paid', Carbon::create(2026, 7, 1));
        $this->makePayout($investment, 'profit', 300, 'scheduled', Carbon::create(2026, 10, 1)); // not paid

        $balance = app(InvestorBalance::class)->for($user);

        $this->assertSame(450.0, $balance->totalProfitPaid);
    }

    public function test_total_profit_expected_sums_all_profit_payouts(): void
    {
        $user = $this->makeUser();
        $investment = $this->makeInvestment($user, 5000);

        $this->makePayout($investment, 'profit', 200, 'paid', Carbon::create(2026, 4, 1));
        $this->makePayout($investment, 'profit', 250, 'scheduled', Carbon::create(2026, 7, 1));
        $this->makePayout($investment, 'profit', null, 'scheduled', Carbon::create(2026, 10, 1)); // null ignored by SUM

        $balance = app(InvestorBalance::class)->for($user);

        $this->assertSame(450.0, $balance->totalProfitExpected);
    }

    public function test_capital_returned_counts_only_paid_capital_payouts(): void
    {
        $user = $this->makeUser();
        $investment = $this->makeInvestment($user, 5000);

        // Capital not yet paid -> not counted.
        $this->makePayout($investment, 'capital', 5000, 'scheduled', Carbon::create(2027, 1, 1));

        $balance = app(InvestorBalance::class)->for($user);
        $this->assertSame(0.0, $balance->capitalReturned);

        // Pay it -> counted.
        $investment->payouts()->capital()->first()->update(['status' => 'paid', 'paid_at' => now()]);

        $balance = app(InvestorBalance::class)->for($user);
        $this->assertSame(5000.0, $balance->capitalReturned);
    }

    public function test_next_payout_returns_nearest_upcoming_by_due_date(): void
    {
        $user = $this->makeUser();
        $investment = $this->makeInvestment($user, 5000);

        $this->makePayout($investment, 'profit', 200, 'paid', Carbon::create(2026, 4, 1));     // paid -> excluded
        $this->makePayout($investment, 'profit', 250, 'due', Carbon::create(2026, 7, 1));      // upcoming (nearest)
        $this->makePayout($investment, 'profit', 300, 'scheduled', Carbon::create(2026, 10, 1)); // upcoming (later)

        $balance = app(InvestorBalance::class)->for($user);

        $this->assertNotNull($balance->nextPayout);
        $this->assertSame('2026-07-01', $balance->nextPayout->due_date->toDateString());
    }

    public function test_next_payout_is_null_when_no_upcoming_payouts(): void
    {
        $user = $this->makeUser();
        $investment = $this->makeInvestment($user, 5000);
        $this->makePayout($investment, 'profit', 200, 'paid', Carbon::create(2026, 4, 1));

        $balance = app(InvestorBalance::class)->for($user);

        $this->assertNull($balance->nextPayout);
    }
}
