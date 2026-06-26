<?php

namespace Tests\Feature;

use App\Enums\KycState;
use App\Enums\KycStatus;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Portal\ProfilePortalService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(?KycStatus $kyc = null, ?KycState $state = null): User
    {
        $user = User::forceCreate([
            'name' => 'سعد المالكي',
            'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
            'phone' => '0500000000',
            'kyc_status' => $kyc?->value,
            'kyc_state' => $state?->value,
            'kyc_submitted_at' => $state ? now() : null,
        ]);
        $user->assignRole('member');

        return $user;
    }

    public function test_profile_page_renders_user_data(): void
    {
        $user = $this->member(KycStatus::Verified);

        $this->actingAs($user)
            ->get('/portal/profile')
            ->assertOk()
            ->assertSee('الملف الشخصي')
            ->assertSee('سعد المالكي')
            ->assertSee($user->email);
    }

    public function test_kyc_card_documents_uploaded(): void
    {
        $this->actingAs($this->member(KycStatus::Pending, KycState::DocumentsUploaded))
            ->get('/portal/profile')
            ->assertOk()
            ->assertSee('تم استلام مستنداتك، وستبدأ المراجعة قريبًا.');
    }

    public function test_kyc_card_under_review(): void
    {
        $this->actingAs($this->member(KycStatus::Pending, KycState::UnderReview))
            ->get('/portal/profile')
            ->assertOk()
            ->assertSee('مستنداتك قيد المراجعة من قبل فريق أساس.');
    }

    public function test_kyc_card_approved(): void
    {
        $this->actingAs($this->member(KycStatus::Verified, KycState::Approved))
            ->get('/portal/profile')
            ->assertOk()
            ->assertSee('تم التحقق من حسابك بنجاح.');
    }

    public function test_kyc_card_rejected_shows_reason(): void
    {
        $user = $this->member(KycStatus::Rejected, KycState::Rejected);
        $user->forceFill(['kyc_rejection_reason' => 'صورة الهوية غير واضحة'])->save();

        $this->actingAs($user)
            ->get('/portal/profile')
            ->assertOk()
            ->assertSee('تم رفض التحقق')
            ->assertSee('صورة الهوية غير واضحة');
    }

    public function test_account_stats(): void
    {
        $user = $this->member(KycStatus::Verified);

        $contract = Contract::create([
            'title' => 'عقد', 'activity_type' => 'تجارة', 'target_amount' => 1_000_000,
            'min_amount' => 1_000, 'duration_months' => 12, 'payouts_count' => 4, 'status' => 'open',
        ]);
        $inv = Investment::create([
            'user_id' => $user->id, 'contract_id' => $contract->id,
            'amount' => 50000, 'status' => 'approved',
        ]);
        Payout::create(['investment_id' => $inv->id, 'type' => 'profit', 'sequence' => 1, 'due_date' => '2026-04-01', 'amount' => 1500, 'status' => 'paid', 'paid_at' => now()]);
        Payout::create(['investment_id' => $inv->id, 'type' => 'profit', 'sequence' => 2, 'due_date' => '2026-07-01', 'amount' => 1500, 'status' => 'due']);

        $stats = app(ProfilePortalService::class)->data($user)['stats'];

        $this->assertSame(1, $stats['investments']);
        $this->assertSame(50000.0, $stats['capital']);
        $this->assertSame(1500.0, $stats['profit']);
        $this->assertSame(2, $stats['payouts']);
    }
}
