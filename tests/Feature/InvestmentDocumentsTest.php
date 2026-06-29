<?php

namespace Tests\Feature;

use App\Actions\Investments\ApproveInvestment;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Feature 12 — Document Center. Verifies the four PDF documents generate as real
 * PDFs and that every download is strictly owner-scoped.
 */
class InvestmentDocumentsTest extends TestCase
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

    private function approvedInvestment(User $user): Investment
    {
        $contract = Contract::create([
            'title' => 'عقد النمو', 'activity_type' => 'تجارة', 'expected_return' => 12,
            'target_amount' => 1_000_000, 'min_amount' => 1_000, 'duration_months' => 12,
            'payouts_count' => 4, 'status' => 'open',
        ]);
        $investment = Investment::create([
            'user_id' => $user->id, 'contract_id' => $contract->id, 'amount' => 5000, 'status' => 'pending',
        ]);
        app(ApproveInvestment::class)->execute($investment);
        $investment->payouts()->where('type', 'profit')->orderBy('sequence')->first()
            ->update(['amount' => 1500, 'status' => 'paid', 'paid_at' => now()]);

        return $investment->refresh();
    }

    private function assertPdf(TestResponse $response): void
    {
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_owner_can_download_contract_statement_and_certificate(): void
    {
        $user = $this->member();
        $investment = $this->approvedInvestment($user);

        $this->assertPdf($this->actingAs($user)->get(route('portal.investments.contract', $investment)));
        $this->assertPdf($this->actingAs($user)->get(route('portal.investments.statement', $investment)));
        $this->assertPdf($this->actingAs($user)->get(route('portal.investments.certificate', $investment)));
    }

    public function test_owner_can_download_a_paid_payout_receipt(): void
    {
        $user = $this->member();
        $investment = $this->approvedInvestment($user);
        $paid = $investment->payouts()->where('status', 'paid')->first();

        $this->assertPdf($this->actingAs($user)->get(route('portal.payouts.receipt', $paid)));
    }

    public function test_receipt_is_unavailable_for_an_unpaid_payout(): void
    {
        $user = $this->member();
        $investment = $this->approvedInvestment($user);
        $scheduled = $investment->payouts()->where('status', 'scheduled')->first();

        $this->actingAs($user)->get(route('portal.payouts.receipt', $scheduled))->assertNotFound();
    }

    public function test_documents_are_owner_scoped(): void
    {
        $owner = $this->member();
        $investment = $this->approvedInvestment($owner);
        $intruder = $this->member();

        $this->actingAs($intruder)->get(route('portal.investments.contract', $investment))->assertForbidden();

        $paid = $investment->payouts()->where('status', 'paid')->first();
        $this->actingAs($intruder)->get(route('portal.payouts.receipt', $paid))->assertForbidden();
    }
}
