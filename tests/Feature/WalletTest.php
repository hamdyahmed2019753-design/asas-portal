<?php

namespace Tests\Feature;

use App\Actions\Payouts\MarkPayoutPaid;
use App\Enums\WalletTransactionReason;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use App\Services\Portal\WalletService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 2 — cash wallet ledger. Capital returns credit the wallet; balance is
 * always credits − debits.
 */
class WalletTest extends TestCase
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
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function approvedInvestment(User $user, float $amount = 5000): Investment
    {
        $contract = Contract::create([
            'title' => 'عقد', 'activity_type' => 'تجارة', 'target_amount' => 1_000_000,
            'min_amount' => 1_000, 'duration_months' => 12, 'payouts_count' => 4, 'status' => 'open',
        ]);

        return Investment::create([
            'user_id' => $user->id, 'contract_id' => $contract->id, 'amount' => $amount, 'status' => 'approved',
        ]);
    }

    public function test_credit_and_debit_compute_the_balance(): void
    {
        $user = $this->member();
        $wallet = app(WalletService::class);

        $wallet->credit($user, 1000, WalletTransactionReason::CapitalReturn);
        $wallet->debit($user, 300, WalletTransactionReason::Withdrawal);

        $this->assertSame(700.0, $wallet->balance($user));
    }

    public function test_paying_a_capital_payout_credits_the_wallet(): void
    {
        Notification::fake();
        $user = $this->member();
        $investment = $this->approvedInvestment($user, 5000);
        $capital = $investment->payouts()->create([
            'type' => 'capital', 'due_date' => now(), 'amount' => 5000, 'status' => 'due',
        ]);

        app(MarkPayoutPaid::class)->execute($capital);

        $this->assertSame(5000.0, app(WalletService::class)->balance($user));
    }

    public function test_paying_a_profit_payout_does_not_credit_the_wallet(): void
    {
        Notification::fake();
        $user = $this->member();
        $investment = $this->approvedInvestment($user);
        $profit = $investment->payouts()->create([
            'type' => 'profit', 'sequence' => 1, 'due_date' => now(), 'amount' => 1500, 'status' => 'due',
        ]);

        app(MarkPayoutPaid::class)->execute($profit);

        $this->assertSame(0.0, app(WalletService::class)->balance($user));
    }

    public function test_wallet_page_renders_balance_and_transactions(): void
    {
        $user = $this->member();
        app(WalletService::class)->credit($user, 2500, WalletTransactionReason::CapitalReturn);

        $this->actingAs($user)->get(route('portal.wallet'))
            ->assertOk()
            ->assertSee('الرصيد المتاح')
            ->assertSee('استرداد رأس المال');
    }
}
