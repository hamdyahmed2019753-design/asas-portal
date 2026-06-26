<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Portal\OnboardingService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalOnboardingTest extends TestCase
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

    private function withProfileAndDocs(): User
    {
        return $this->member([
            'phone' => '0551234567',
            'city' => 'الرياض',
            'country' => 'السعودية',
            'identity_document_path' => 'onboarding/x/id.jpg',
            'iban_document_path' => 'onboarding/x/iban.jpg',
            'address_document_path' => 'onboarding/x/addr.jpg',
        ]);
    }

    private function onboarded(): User
    {
        return $this->member([
            'phone' => '0551234567', 'city' => 'جدة', 'country' => 'السعودية',
            'identity_document_path' => 'a', 'iban_document_path' => 'b', 'address_document_path' => 'c',
            'terms_accepted_at' => now(), 'onboarding_completed_at' => now(),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/portal/onboarding')->assertRedirect(route('login'));
    }

    public function test_wizard_shows_for_incomplete_user(): void
    {
        $this->actingAs($this->member())
            ->get('/portal/onboarding')
            ->assertOk()
            ->assertSee('إكمال التسجيل')
            ->assertSee('استكمال البيانات');
    }

    public function test_completed_user_is_redirected_to_dashboard(): void
    {
        $this->actingAs($this->onboarded())
            ->get('/portal/onboarding')
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_step_one_saves_profile_and_advances(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post('/portal/onboarding/profile', [
            'name' => 'خالد العتيبي',
            'phone' => '0509998888',
            'city' => 'الدمام',
            'country' => 'السعودية',
        ])->assertRedirect(route('portal.onboarding'));

        $user->refresh();
        $this->assertSame('الدمام', $user->city);
        $this->assertSame('السعودية', $user->country);
        $this->assertSame('0509998888', $user->phone);
        $this->assertSame(2, app(OnboardingService::class)->currentStep($user));
    }

    public function test_step_two_stores_documents_and_advances(): void
    {
        Storage::fake('local');
        $user = $this->member(['phone' => '055', 'city' => 'الرياض', 'country' => 'السعودية']);

        $this->actingAs($user)->post('/portal/onboarding/documents', [
            'identity' => UploadedFile::fake()->create('id.pdf', 120, 'application/pdf'),
            'iban' => UploadedFile::fake()->create('iban.pdf', 120, 'application/pdf'),
            'address' => UploadedFile::fake()->create('addr.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('portal.onboarding'));

        $user->refresh();
        $this->assertNotNull($user->identity_document_path);
        Storage::disk('local')->assertExists($user->identity_document_path);
        Storage::disk('local')->assertExists($user->iban_document_path);
        Storage::disk('local')->assertExists($user->address_document_path);
        $this->assertSame(3, app(OnboardingService::class)->currentStep($user));
    }

    public function test_step_three_completes_onboarding(): void
    {
        $user = $this->withProfileAndDocs();

        $this->actingAs($user)->post('/portal/onboarding/terms', ['terms' => '1'])
            ->assertRedirect(route('portal.onboarding', ['done' => 1]));

        $user->refresh();
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertTrue($user->hasCompletedOnboarding());
    }

    public function test_success_screen_is_shown_after_completion(): void
    {
        $this->actingAs($this->onboarded())
            ->get(route('portal.onboarding', ['done' => 1]))
            ->assertOk()
            ->assertSee('تم إكمال تسجيلك بنجاح');
    }

    public function test_profile_step_validates_required_fields(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post('/portal/onboarding/profile', ['name' => 'فلان'])
            ->assertSessionHasErrors(['phone', 'city', 'country']);

        $this->assertNull($user->fresh()->city);
    }

    public function test_documents_step_requires_all_files(): void
    {
        $this->actingAs($this->member())
            ->post('/portal/onboarding/documents', [])
            ->assertSessionHasErrors(['identity', 'iban', 'address']);
    }

    public function test_terms_must_be_accepted(): void
    {
        $user = $this->withProfileAndDocs();

        $this->actingAs($user)->post('/portal/onboarding/terms', [])
            ->assertSessionHasErrors('terms');

        $this->assertFalse($user->fresh()->hasCompletedOnboarding());
    }

    public function test_progress_reflects_completed_steps(): void
    {
        $service = app(OnboardingService::class);

        $this->assertSame(0, $service->progress($this->member()));
        $this->assertSame(67, $service->progress($this->withProfileAndDocs()));
        $this->assertSame(100, $service->progress($this->onboarded()));
    }

    public function test_dashboard_shows_onboarding_cta_for_incomplete_user(): void
    {
        $this->actingAs($this->member())
            ->get('/portal')
            ->assertOk()
            ->assertSee('إكمال التسجيل');
    }

    public function test_wizard_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();

        DB::enableQueryLog();
        $this->actingAs($user)->get('/portal/onboarding')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $count);
    }
}
