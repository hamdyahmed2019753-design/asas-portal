<?php

namespace Tests\Feature;

use App\Actions\Investments\RejectInvestment;
use App\Enums\KycState;
use App\Enums\WalletTransactionReason;
use App\Models\Contract;
use App\Models\User;
use App\Services\Portal\SubscriptionService;
use App\Services\Portal\WalletService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 4 — reinvest from the wallet. Buying a contract from the balance debits
 * the wallet and creates a pending investment (admin approves); a rejection
 * refunds the wallet.
 */
class WalletReinvestTest extends TestCase
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
            'kyc_state' => KycState::Approved->value,
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function funded(float $amount): User
    {
        $user = $this->member();
        app(WalletService::class)->credit($user, $amount, WalletTransactionReason::CapitalReturn);

        return $user;
    }

    private function contract(): Contract
    {
        return Contract::create([
            'title' => 'صندوق النمو', 'activity_type' => 'تجارة', 'target_amount' => 1_000_000,
            'min_amount' => 5_000, 'max_amount' => 50_000, 'share_price' => 1_000,
            'duration_months' => 12, 'payouts_count' => 4, 'status' => 'open',
        ]);
    }

    public function test_subscribe_from_wallet_debits_and_creates_pending(): void
    {
        Notification::fake();
        $user = $this->funded(20000);

        $this->actingAs($user)
            ->post(route('portal.contracts.subscribe', $this->contract()), ['shares' => 10, 'method' => 'wallet'])
            ->assertRedirect();

        $investment = $user->investments()->first();
        $this->assertSame('pending', $investment->status->value);
        $this->assertSame('wallet', $investment->payment_method->value);
        $this->assertSame(10000.0, app(WalletService::class)->balance($user)); // 20000 − 10000
    }

    public function test_wallet_payment_falls_back_to_bank_when_balance_insufficient(): void
    {
        $user = $this->funded(3000);

        $this->actingAs($user)
            ->post(route('portal.contracts.subscribe', $this->contract()), ['shares' => 10, 'method' => 'wallet']);

        $investment = $user->investments()->first();
        $this->assertSame('pending_payment', $investment->status->value); // bank flow
        $this->assertSame('bank_transfer', $investment->payment_method->value);
        $this->assertSame(3000.0, app(WalletService::class)->balance($user)); // untouched
    }

    public function test_rejecting_a_wallet_investment_refunds_the_wallet(): void
    {
        Notification::fake();
        $user = $this->funded(20000);
        $investment = app(SubscriptionService::class)->subscribeFromWallet($user, $this->contract(), 10);

        $this->assertSame(10000.0, app(WalletService::class)->balance($user)); // debited

        app(RejectInvestment::class)->execute($investment, 'غير مؤهل');

        $this->assertSame('rejected', $investment->refresh()->status->value);
        $this->assertSame(20000.0, app(WalletService::class)->balance($user)); // refunded
    }
}
