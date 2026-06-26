<?php

namespace Tests\Feature\Console;

use App\Enums\PayoutStatus;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RefreshPayoutsTest extends TestCase
{
    use RefreshDatabase;

    private Investment $investment;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Investor',
            'email' => uniqid('inv_').'@test.local',
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

        $this->investment = Investment::create([
            'user_id' => $user->id,
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'approved',
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2027, 1, 1),
        ]);
    }

    private function makePayout(string $status, Carbon $due, ?float $amount = 200): Payout
    {
        return Payout::create([
            'investment_id' => $this->investment->id,
            'type' => 'profit',
            'sequence' => 1,
            'due_date' => $due,
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    public function test_scheduled_payout_past_due_becomes_due(): void
    {
        $payout = $this->makePayout('scheduled', now()->subDay());

        $this->artisan('payouts:refresh')->assertExitCode(0);

        $this->assertSame(PayoutStatus::Due, $payout->refresh()->status);
    }

    public function test_scheduled_payout_due_today_becomes_due(): void
    {
        $payout = $this->makePayout('scheduled', now());

        $this->artisan('payouts:refresh')->assertExitCode(0);

        $this->assertSame(PayoutStatus::Due, $payout->refresh()->status);
    }

    public function test_scheduled_payout_in_the_future_stays_scheduled(): void
    {
        $payout = $this->makePayout('scheduled', now()->addMonth());

        $this->artisan('payouts:refresh')->assertExitCode(0);

        $this->assertSame(PayoutStatus::Scheduled, $payout->refresh()->status);
    }

    public function test_paid_payout_is_not_changed(): void
    {
        $payout = $this->makePayout('paid', now()->subMonth());

        $this->artisan('payouts:refresh')->assertExitCode(0);

        $this->assertSame(PayoutStatus::Paid, $payout->refresh()->status);
    }
}
