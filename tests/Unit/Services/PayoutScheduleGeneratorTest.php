<?php

namespace Tests\Unit\Services;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use App\Services\PayoutScheduleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayoutScheduleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function makeApprovedInvestment(int $durationMonths, int $payoutsCount, float $amount, Carbon $startDate): Investment
    {
        $user = User::create([
            'name' => 'Investor',
            'email' => uniqid('inv_').'@test.local',
            'password' => 'secret123',
        ]);

        $contract = Contract::create([
            'title' => 'عقد',
            'activity_type' => 'تجارة',
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'max_amount' => 100_000,
            'duration_months' => $durationMonths,
            'payouts_count' => $payoutsCount,
            'status' => 'open',
        ]);

        return Investment::create([
            'user_id' => $user->id,
            'contract_id' => $contract->id,
            'amount' => $amount,
            'status' => 'approved',
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths($durationMonths),
        ]);
    }

    public function test_it_generates_four_profit_payouts_and_one_capital_for_12_months(): void
    {
        $start = Carbon::create(2026, 1, 1);
        $investment = $this->makeApprovedInvestment(12, 4, 5000, $start);

        $payouts = app(PayoutScheduleGenerator::class)->generate($investment);

        $this->assertCount(5, $payouts);
        $this->assertSame(4, $investment->payouts()->profit()->count());
        $this->assertSame(1, $investment->payouts()->capital()->count());
    }

    public function test_profit_payouts_have_evenly_spaced_dates_and_sequences_for_12_4(): void
    {
        $start = Carbon::create(2026, 1, 1);
        $investment = $this->makeApprovedInvestment(12, 4, 5000, $start);

        app(PayoutScheduleGenerator::class)->generate($investment);

        $profit = $investment->payouts()->profit()->orderBy('sequence')->get();

        $this->assertSame([1, 2, 3, 4], $profit->pluck('sequence')->all());
        $this->assertSame('2026-04-01', $profit[0]->due_date->toDateString()); // +3
        $this->assertSame('2026-07-01', $profit[1]->due_date->toDateString()); // +6
        $this->assertSame('2026-10-01', $profit[2]->due_date->toDateString()); // +9
        $this->assertSame('2027-01-01', $profit[3]->due_date->toDateString()); // +12
    }

    public function test_profit_payout_amount_is_null_and_status_scheduled(): void
    {
        $start = Carbon::create(2026, 1, 1);
        $investment = $this->makeApprovedInvestment(12, 4, 5000, $start);

        app(PayoutScheduleGenerator::class)->generate($investment);

        foreach ($investment->payouts()->profit()->get() as $payout) {
            $this->assertNull($payout->amount);
            $this->assertSame(PayoutType::Profit, $payout->type);
            $this->assertSame(PayoutStatus::Scheduled, $payout->status);
        }
    }

    public function test_capital_payout_has_investment_amount_and_due_at_contract_end(): void
    {
        $start = Carbon::create(2026, 1, 1);
        $investment = $this->makeApprovedInvestment(12, 4, 5000, $start);

        app(PayoutScheduleGenerator::class)->generate($investment);

        $capital = $investment->payouts()->capital()->sole();

        $this->assertSame(PayoutType::Capital, $capital->type);
        $this->assertNull($capital->sequence);
        $this->assertSame('5000.00', $capital->amount);
        $this->assertSame('2027-01-01', $capital->due_date->toDateString()); // start + 12 months
        $this->assertSame(PayoutStatus::Scheduled, $capital->status);
    }

    public function test_it_generates_eight_profit_payouts_with_correct_dates_for_24_months(): void
    {
        $start = Carbon::create(2026, 1, 1);
        $investment = $this->makeApprovedInvestment(24, 8, 8000, $start);

        $payouts = app(PayoutScheduleGenerator::class)->generate($investment);

        $this->assertCount(9, $payouts); // 8 profit + 1 capital
        $this->assertSame(8, $investment->payouts()->profit()->count());

        $profit = $investment->payouts()->profit()->orderBy('sequence')->get();
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], $profit->pluck('sequence')->all());

        // 24 months / 8 payouts => every 3 months.
        $expected = ['2026-04-01', '2026-07-01', '2026-10-01', '2027-01-01', '2027-04-01', '2027-07-01', '2027-10-01', '2028-01-01'];
        $this->assertSame($expected, $profit->map(fn ($p) => $p->due_date->toDateString())->all());

        // Capital due at start + 24 months.
        $this->assertSame('2028-01-01', $investment->payouts()->capital()->sole()->due_date->toDateString());
    }
}
