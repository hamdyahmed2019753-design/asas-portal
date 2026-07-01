<?php

namespace Tests\Feature;

use App\Actions\Investments\ApproveInvestment;
use App\Enums\DocumentCategory;
use App\Enums\InvestmentStatus;
use App\Enums\KycState;
use App\Enums\PayoutType;
use App\Models\Contract;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Support\Settings;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature 12 — direct bank-transfer subscription: subscribe (shares) → transfer →
 * receipt → admin approval that generates payouts on the contract's dates.
 */
class PortalSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(?KycState $kyc = KycState::Approved): User
    {
        $user = User::forceCreate([
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
            'kyc_state' => $kyc?->value,
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function contract(array $overrides = []): Contract
    {
        return Contract::create(array_merge([
            'title' => 'صندوق النمو', 'activity_type' => 'تجارة', 'expected_return' => 12,
            'target_amount' => 1_000_000, 'min_amount' => 5_000, 'max_amount' => 50_000,
            'share_price' => 1_000, 'duration_months' => 12, 'payouts_count' => 4,
            'payout_schedule' => ['2026-09-01', '2026-12-01'], 'status' => 'open',
        ], $overrides));
    }

    public function test_approved_investor_can_subscribe_and_is_sent_to_transfer(): void
    {
        $user = $this->member();
        $contract = $this->contract();

        $this->actingAs($user)
            ->post(route('portal.contracts.subscribe', $contract), ['shares' => 10])
            ->assertRedirect(route('portal.investments.transfer', $user->investments()->first()));

        $investment = $user->investments()->first();
        $this->assertSame(InvestmentStatus::PendingPayment, $investment->status);
        $this->assertSame(10, $investment->shares);
        $this->assertSame('10000.00', (string) $investment->amount); // 10 × 1000
    }

    public function test_non_kyc_investor_cannot_subscribe(): void
    {
        $user = $this->member(KycState::UnderReview);

        $this->actingAs($user)
            ->post(route('portal.contracts.subscribe', $this->contract()), ['shares' => 10])
            ->assertForbidden();

        $this->assertSame(0, $user->investments()->count());
    }

    public function test_shares_below_minimum_are_rejected(): void
    {
        $user = $this->member();

        // min_amount 5000 ÷ price 1000 → min 5 shares.
        $this->actingAs($user)
            ->post(route('portal.contracts.subscribe', $this->contract()), ['shares' => 2])
            ->assertSessionHasErrors('shares');

        $this->assertSame(0, $user->investments()->count());
    }

    public function test_transfer_page_shows_configured_bank_accounts(): void
    {
        app(Settings::class)->setMany([
            'bank.1.name' => 'البنك الأهلي السعودي',
            'bank.1.iban' => 'SA1234567890',
        ]);
        $user = $this->member();
        $investment = $user->investments()->create([
            'contract_id' => $this->contract()->id, 'shares' => 10, 'amount' => 10000,
            'status' => InvestmentStatus::PendingPayment->value,
        ]);

        $this->actingAs($user)
            ->get(route('portal.investments.transfer', $investment))
            ->assertOk()
            ->assertSee('البنك الأهلي السعودي')
            ->assertSee('SA1234567890');
    }

    public function test_transfer_page_is_owner_scoped(): void
    {
        $owner = $this->member();
        $investment = $owner->investments()->create([
            'contract_id' => $this->contract()->id, 'shares' => 10, 'amount' => 10000,
            'status' => InvestmentStatus::PendingPayment->value,
        ]);

        $this->actingAs($this->member())
            ->get(route('portal.investments.transfer', $investment))
            ->assertForbidden();
    }

    public function test_uploading_receipt_moves_to_payment_submitted_and_alerts_admins(): void
    {
        Storage::fake('local');
        Notification::fake();
        $user = $this->member();
        $investment = $user->investments()->create([
            'contract_id' => $this->contract()->id, 'shares' => 10, 'amount' => 10000,
            'status' => InvestmentStatus::PendingPayment->value,
        ]);
        $admin = $this->member();
        $admin->syncRoles('admin');

        $this->actingAs($user)
            ->post(route('portal.investments.receipt', $investment), [
                'receipt' => UploadedFile::fake()->create('transfer.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('portal.investments.show', $investment));

        $investment->refresh();
        $this->assertSame(InvestmentStatus::PaymentSubmitted, $investment->status);
        $this->assertNotNull($investment->receipt_path);
        Storage::disk('local')->assertExists($investment->receipt_path);
        $this->assertSame(DocumentCategory::PaymentReceipt, $user->documents()->first()->category);
        Notification::assertSentTo($admin, AdminNotification::class);
    }

    public function test_subscribe_modal_has_no_click_outside_regression(): void
    {
        $this->actingAs($this->member())
            ->get(route('contracts.show', $this->contract()))
            ->assertOk()
            ->assertSee('اشتراك في العقد')
            ->assertSee('/portal/contracts/', false)
            ->assertSee('class="ip-modal"', false)
            ->assertDontSee('ip-modal" @click.outside', false);
    }

    public function test_duplicate_subscription_redirects_instead_of_creating_another(): void
    {
        $user = $this->member();
        $contract = $this->contract();
        $existing = $user->investments()->create([
            'contract_id' => $contract->id, 'shares' => 10, 'amount' => 10000,
            'status' => InvestmentStatus::PendingPayment->value,
        ]);

        $this->actingAs($user)
            ->post(route('portal.contracts.subscribe', $contract), ['shares' => 20])
            ->assertRedirect(route('portal.investments.transfer', $existing));

        $this->assertSame(1, $user->investments()->where('contract_id', $contract->id)->count());
    }

    public function test_admin_approval_confirms_payment_and_generates_payouts_on_contract_dates(): void
    {
        Notification::fake();
        $user = $this->member();
        $contract = $this->contract();
        $investment = $user->investments()->create([
            'contract_id' => $contract->id, 'shares' => 10, 'amount' => 10000,
            'status' => InvestmentStatus::PaymentSubmitted->value,
        ]);

        app(ApproveInvestment::class)->execute($investment);

        $investment->refresh();
        $this->assertSame(InvestmentStatus::Approved, $investment->status);
        $this->assertNotNull($investment->payment_confirmed_at);

        // Profit payouts fall on the contract's declared dates (+ one capital row).
        $profitDates = $investment->payouts()->where('type', PayoutType::Profit->value)
            ->orderBy('due_date')->pluck('due_date')->map->format('Y-m-d')->all();
        $this->assertSame(['2026-09-01', '2026-12-01'], $profitDates);
        $this->assertSame(1, $investment->payouts()->where('type', PayoutType::Capital->value)->count());
    }
}
