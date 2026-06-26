<?php

namespace Tests\Feature;

use App\Enums\KycState;
use App\Enums\KycStatus;
use App\Models\User;
use App\Services\Portal\KycService;
use App\Services\Portal\OnboardingService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PortalKycTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(array $attributes = []): User
    {
        $user = User::forceCreate(array_merge([
            'name' => 'مستثمر',
            'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ], $attributes));
        $user->assignRole('member');

        return $user;
    }

    private function admin(): User
    {
        $user = User::forceCreate([
            'name' => 'مدير', 'email' => uniqid('a_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('admin');

        return $user;
    }

    private function onboarded(?KycState $state = KycState::DocumentsUploaded): User
    {
        return $this->member([
            'phone' => '055', 'city' => 'الرياض', 'country' => 'السعودية',
            'identity_document_path' => 'a', 'iban_document_path' => 'b', 'address_document_path' => 'c',
            'terms_accepted_at' => now(), 'onboarding_completed_at' => now(),
            'kyc_state' => $state?->value, 'kyc_submitted_at' => now(),
        ]);
    }

    // ----- Workflow -----

    public function test_completing_onboarding_submits_kyc(): void
    {
        $user = $this->member([
            'phone' => '055', 'city' => 'الرياض', 'country' => 'السعودية',
            'identity_document_path' => 'a', 'iban_document_path' => 'b', 'address_document_path' => 'c',
        ]);

        app(OnboardingService::class)->complete($user);

        $this->assertSame(KycState::DocumentsUploaded, $user->fresh()->kyc_state);
        $this->assertNotNull($user->fresh()->kyc_submitted_at);
    }

    public function test_admin_can_start_review(): void
    {
        $user = $this->onboarded();

        app(KycService::class)->startReview($user);

        $this->assertSame(KycState::UnderReview, $user->fresh()->kyc_state);
    }

    public function test_admin_can_approve_kyc(): void
    {
        $user = $this->onboarded(KycState::UnderReview);

        app(KycService::class)->approve($user);
        $user->refresh();

        $this->assertSame(KycState::Approved, $user->kyc_state);
        $this->assertSame(KycStatus::Verified, $user->kyc_status); // legacy column synced
        $this->assertNotNull($user->kyc_reviewed_at);
    }

    public function test_admin_can_reject_kyc_with_reason(): void
    {
        $user = $this->onboarded(KycState::UnderReview);

        app(KycService::class)->reject($user, 'صورة الهوية غير واضحة');
        $user->refresh();

        $this->assertSame(KycState::Rejected, $user->kyc_state);
        $this->assertSame(KycStatus::Rejected, $user->kyc_status);
        $this->assertSame('صورة الهوية غير واضحة', $user->kyc_rejection_reason);
    }

    // ----- Dashboard alert -----

    public function test_dashboard_shows_kyc_alert_when_not_approved(): void
    {
        $this->actingAs($this->onboarded(KycState::UnderReview))
            ->get('/portal')
            ->assertOk()
            ->assertSee('التحقق من هويتك');
    }

    public function test_dashboard_hides_kyc_alert_when_approved(): void
    {
        $this->actingAs($this->onboarded(KycState::Approved))
            ->get('/portal')
            ->assertOk()
            ->assertDontSee('التحقق من هويتك');
    }

    // ----- Document access (signed + auth + ownership) -----

    public function test_owner_can_download_own_document_via_signed_url(): void
    {
        Storage::fake('local');
        $user = $this->onboarded();
        Storage::disk('local')->put('onboarding/x/id.pdf', 'pdf-bytes');
        $user->forceFill(['identity_document_path' => 'onboarding/x/id.pdf'])->save();

        $url = URL::temporarySignedRoute('portal.kyc.document', now()->addMinutes(10), ['type' => 'identity']);

        $this->actingAs($user)->get($url)->assertOk();
    }

    public function test_unsigned_document_url_is_rejected(): void
    {
        $user = $this->onboarded();

        // No signature → 403 from the `signed` middleware.
        $this->actingAs($user)->get(route('portal.kyc.document', ['type' => 'identity']))
            ->assertForbidden();
    }

    public function test_admin_route_blocks_non_owner_non_admin(): void
    {
        $owner = $this->onboarded();
        $intruder = $this->member();

        $url = URL::temporarySignedRoute('kyc.admin.document', now()->addMinutes(10), [
            'user' => $owner->id, 'type' => 'identity',
        ]);

        // Valid signature, but a different non-admin member → policy denies.
        $this->actingAs($intruder)->get($url)->assertForbidden();
    }

    public function test_admin_can_download_any_document(): void
    {
        Storage::fake('local');
        $owner = $this->onboarded();
        Storage::disk('local')->put('onboarding/x/id.pdf', 'pdf-bytes');
        $owner->forceFill(['identity_document_path' => 'onboarding/x/id.pdf'])->save();

        $url = URL::temporarySignedRoute('kyc.admin.document', now()->addMinutes(10), [
            'user' => $owner->id, 'type' => 'identity',
        ]);

        $this->actingAs($this->admin())->get($url)->assertOk();
    }

    // ----- Policies -----

    public function test_only_admin_may_review_kyc(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $other = $this->onboarded();

        $this->assertTrue($admin->can('reviewKyc', $other));
        $this->assertFalse($member->can('reviewKyc', $other));
    }

    public function test_kyc_document_ownership_policy(): void
    {
        $owner = $this->onboarded();
        $intruder = $this->member();

        $this->assertTrue($owner->can('viewKycDocuments', $owner));
        $this->assertFalse($intruder->can('viewKycDocuments', $owner));
        $this->assertTrue($this->admin()->can('viewKycDocuments', $owner));
    }
}
