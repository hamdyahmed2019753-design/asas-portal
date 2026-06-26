<?php

namespace Tests\Feature\Actions;

use App\Actions\Payouts\MarkPayoutPaid;
use App\Enums\PayoutStatus;
use App\Exceptions\PayoutAlreadyPaidException;
use App\Exceptions\PayoutAmountMissingException;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarkPayoutPaidTest extends TestCase
{
    use RefreshDatabase;

    private function makePayout(string $type, ?float $amount, string $status): Payout
    {
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

        $investment = Investment::create([
            'user_id' => $user->id,
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'approved',
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2027, 1, 1),
        ]);

        return Payout::create([
            'investment_id' => $investment->id,
            'type' => $type,
            'sequence' => $type === 'profit' ? 1 : null,
            'due_date' => Carbon::create(2026, 4, 1),
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    public function test_it_marks_a_profit_payout_with_amount_as_paid(): void
    {
        $payout = $this->makePayout('profit', 200, 'scheduled');

        app(MarkPayoutPaid::class)->execute($payout);

        $payout->refresh();
        $this->assertSame(PayoutStatus::Paid, $payout->status);
        $this->assertNotNull($payout->paid_at);
    }

    public function test_it_marks_a_capital_payout_as_paid(): void
    {
        $payout = $this->makePayout('capital', 5000, 'scheduled');

        app(MarkPayoutPaid::class)->execute($payout);

        $this->assertSame(PayoutStatus::Paid, $payout->refresh()->status);
    }

    public function test_it_cannot_pay_a_profit_payout_with_null_amount(): void
    {
        $payout = $this->makePayout('profit', null, 'scheduled');

        $this->expectException(PayoutAmountMissingException::class);

        app(MarkPayoutPaid::class)->execute($payout);
    }

    public function test_null_amount_payout_stays_unpaid_after_failed_attempt(): void
    {
        $payout = $this->makePayout('profit', null, 'scheduled');

        try {
            app(MarkPayoutPaid::class)->execute($payout);
        } catch (PayoutAmountMissingException) {
            // expected
        }

        $this->assertSame(PayoutStatus::Scheduled, $payout->refresh()->status);
        $this->assertNull($payout->paid_at);
    }

    public function test_it_cannot_pay_an_already_paid_payout(): void
    {
        $payout = $this->makePayout('profit', 200, 'paid');

        $this->expectException(PayoutAlreadyPaidException::class);

        app(MarkPayoutPaid::class)->execute($payout);
    }
}
