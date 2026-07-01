<?php

namespace Tests\Feature;

use App\Actions\Payouts\MarkPayoutPaid;
use App\Enums\KycState;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 1 — investor payout bank account + admin-uploaded transfer receipts.
 */
class BankAccountAndPayoutReceiptTest extends TestCase
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

    public function test_investor_can_save_a_bank_account(): void
    {
        $user = $this->member();

        $this->actingAs($user)->patch(route('portal.settings.bank'), [
            'bank_name' => 'مصرف الإنماء',
            'bank_account_name' => 'خالد العتيبي',
            'bank_iban' => 'SA03 8000 0000 6080 1016 7519',
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->hasBankAccount());
        $this->assertSame('SA0380000000608010167519', $user->bank_iban); // normalised
    }

    public function test_invalid_iban_is_rejected(): void
    {
        $this->actingAs($this->member())->patch(route('portal.settings.bank'), [
            'bank_name' => 'بنك', 'bank_account_name' => 'خالد', 'bank_iban' => '12345',
        ])->assertSessionHasErrors('bank_iban');
    }

    public function test_profit_payout_receipt_is_stored_and_investor_downloads_the_uploaded_file(): void
    {
        Storage::fake('local');
        Notification::fake();

        $user = $this->member();
        $contract = Contract::create([
            'title' => 'عقد', 'activity_type' => 'تجارة', 'target_amount' => 1_000_000,
            'min_amount' => 1_000, 'duration_months' => 12, 'payouts_count' => 4, 'status' => 'open',
        ]);
        $investment = Investment::create([
            'user_id' => $user->id, 'contract_id' => $contract->id, 'amount' => 5000, 'status' => 'approved',
        ]);
        $payout = $investment->payouts()->create([
            'type' => 'profit', 'sequence' => 1, 'due_date' => now(), 'amount' => 1500, 'status' => 'due',
        ]);

        $path = 'payout-receipts/transfer.pdf';
        Storage::disk('local')->put($path, '%PDF-fake');

        app(MarkPayoutPaid::class)->execute($payout, $path);

        $payout->refresh();
        $this->assertSame('paid', $payout->status->value);
        $this->assertSame($path, $payout->receipt_path);

        // The owner downloads the admin-uploaded receipt (served as an attachment).
        $response = $this->actingAs($user)->get(route('portal.payouts.receipt', $payout));
        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }
}
