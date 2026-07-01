<?php

namespace Tests\Feature;

use App\Enums\WalletTransactionReason;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\WithdrawalPaidNotification;
use App\Notifications\WithdrawalRejectedNotification;
use App\Services\Portal\WalletService;
use App\Services\Portal\WithdrawalService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 3 — wallet withdrawals. Request holds (debits) the balance; admin pays
 * with a receipt or rejects (which refunds the wallet).
 */
class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(bool $withBank = true): User
    {
        $user = User::forceCreate([
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
            'bank_name' => $withBank ? 'مصرف الإنماء' : null,
            'bank_account_name' => $withBank ? 'خالد' : null,
            'bank_iban' => $withBank ? 'SA0380000000608010167519' : null,
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

    public function test_request_holds_balance_and_notifies_admins(): void
    {
        Notification::fake();
        $admin = $this->member(false);
        $admin->syncRoles('admin');
        $user = $this->funded(5000);

        $this->actingAs($user)
            ->post(route('portal.wallet.withdraw.store'), ['amount' => 2000])
            ->assertRedirect(route('portal.wallet'));

        $this->assertSame(3000.0, app(WalletService::class)->balance($user));
        $this->assertSame(1, $user->withdrawals()->where('status', 'pending')->count());
        Notification::assertSentTo($admin, AdminNotification::class);
    }

    public function test_cannot_withdraw_more_than_balance(): void
    {
        $user = $this->funded(1000);

        $this->actingAs($user)
            ->post(route('portal.wallet.withdraw.store'), ['amount' => 5000])
            ->assertSessionHasErrors('amount');

        $this->assertSame(1000.0, app(WalletService::class)->balance($user));
    }

    public function test_admin_pays_withdrawal_with_receipt_and_investor_downloads_it(): void
    {
        Storage::fake('local');
        Notification::fake();
        $user = $this->funded(5000);
        $withdrawal = app(WithdrawalService::class)->request($user, 2000);

        $path = 'withdrawal-receipts/transfer.pdf';
        Storage::disk('local')->put($path, '%PDF');
        app(WithdrawalService::class)->markPaid($withdrawal, $path);

        $withdrawal->refresh();
        $this->assertSame('paid', $withdrawal->status->value);
        $this->assertSame($path, $withdrawal->receipt_path);
        $this->assertSame(3000.0, app(WalletService::class)->balance($user)); // still held
        Notification::assertSentTo($user, WithdrawalPaidNotification::class);

        $this->actingAs($user)->get(route('portal.withdrawals.receipt', $withdrawal))->assertOk();
    }

    public function test_rejecting_a_withdrawal_refunds_the_wallet(): void
    {
        Notification::fake();
        $user = $this->funded(5000);
        $withdrawal = app(WithdrawalService::class)->request($user, 2000); // balance → 3000

        app(WithdrawalService::class)->reject($withdrawal, 'بيانات الحساب غير صحيحة');

        $this->assertSame('rejected', $withdrawal->refresh()->status->value);
        $this->assertSame(5000.0, app(WalletService::class)->balance($user)); // refunded
        Notification::assertSentTo($user, WithdrawalRejectedNotification::class);
    }

    public function test_withdrawal_receipt_is_owner_scoped(): void
    {
        Storage::fake('local');
        Notification::fake();
        $owner = $this->funded(5000);
        $withdrawal = app(WithdrawalService::class)->request($owner, 2000);
        Storage::disk('local')->put($path = 'withdrawal-receipts/r.pdf', 'x');
        app(WithdrawalService::class)->markPaid($withdrawal, $path);

        $this->actingAs($this->member())
            ->get(route('portal.withdrawals.receipt', $withdrawal))
            ->assertForbidden();
    }

    public function test_investor_without_bank_account_is_redirected_to_settings(): void
    {
        $user = $this->member(withBank: false);

        $this->actingAs($user)
            ->get(route('portal.wallet.withdraw'))
            ->assertRedirect(route('portal.settings'));
    }
}
