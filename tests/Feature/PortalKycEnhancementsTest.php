<?php

namespace Tests\Feature;

use App\Enums\KycState;
use App\Http\Middleware\EnsureKycApproved;
use App\Models\Contract;
use App\Models\ContractInterest;
use App\Models\Investment;
use App\Models\User;
use App\Notifications\KycApprovedNotification;
use App\Notifications\KycRejectedNotification;
use App\Services\Portal\KycService;
use App\Support\Settings;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PortalKycEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(?KycState $state = null): User
    {
        $user = User::forceCreate([
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
            'kyc_state' => $state?->value, 'kyc_submitted_at' => $state ? now() : null,
            'onboarding_completed_at' => $state ? now() : null,
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function openContract(): Contract
    {
        return Contract::create([
            'title' => 'صندوق النمو', 'activity_type' => 'تجارة', 'target_amount' => 1_000_000,
            'min_amount' => 1_000, 'share_price' => 1_000, 'duration_months' => 12,
            'payouts_count' => 4, 'status' => 'open',
        ]);
    }

    // ----- 1. Investment actions gated on KYC -----

    public function test_investment_and_interest_creation_requires_approved_kyc(): void
    {
        $approved = $this->member(KycState::Approved);
        $pending = $this->member(KycState::UnderReview);

        $this->assertTrue($approved->can('create', Investment::class));
        $this->assertTrue($approved->can('create', ContractInterest::class));
        $this->assertFalse($pending->can('create', Investment::class));
        $this->assertFalse($pending->can('create', ContractInterest::class));
    }

    public function test_contract_page_shows_subscribe_cta_only_when_approved(): void
    {
        $contract = $this->openContract();

        $this->actingAs($this->member(KycState::Approved))
            ->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('اشتراك في العقد');

        $this->actingAs($this->member(KycState::UnderReview))
            ->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertDontSee('اشتراك في العقد')
            ->assertSee('يجب اكتمال التحقق من هويتك');
    }

    public function test_kyc_middleware_blocks_unapproved_users(): void
    {
        $middleware = app(EnsureKycApproved::class);
        $next = fn ($request): Response => new Response('passed');

        $approvedReq = Request::create('/x');
        $approvedReq->setUserResolver(fn () => $this->member(KycState::Approved));
        $this->assertSame('passed', $middleware->handle($approvedReq, $next)->getContent());

        $blockedReq = Request::create('/x');
        $blockedReq->setUserResolver(fn () => $this->member(KycState::UnderReview));
        $this->assertEquals(302, $middleware->handle($blockedReq, $next)->getStatusCode());
    }

    // ----- 2. Resubmission flow -----

    public function test_rejected_user_can_open_resubmission_form(): void
    {
        $this->actingAs($this->member(KycState::Rejected))
            ->get('/portal/kyc/resubmit')
            ->assertOk()
            ->assertSee('إعادة رفع المستندات');
    }

    public function test_non_rejected_user_cannot_open_resubmission_form(): void
    {
        $this->actingAs($this->member(KycState::UnderReview))
            ->get('/portal/kyc/resubmit')
            ->assertForbidden();
    }

    public function test_resubmission_transitions_back_to_documents_uploaded(): void
    {
        Storage::fake('local');
        $user = $this->member(KycState::Rejected);
        $user->forceFill(['kyc_rejection_reason' => 'صورة غير واضحة', 'kyc_reviewed_at' => now()])->save();

        $this->actingAs($user)->post('/portal/kyc/resubmit', [
            'identity' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            'iban' => UploadedFile::fake()->create('iban.pdf', 100, 'application/pdf'),
            'address' => UploadedFile::fake()->create('addr.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('portal.profile'));

        $user->refresh();
        $this->assertSame(KycState::DocumentsUploaded, $user->kyc_state);
        $this->assertNull($user->kyc_rejection_reason);
        $this->assertNull($user->kyc_reviewed_at);
        Storage::disk('local')->assertExists($user->identity_document_path);

        // ...and an admin can then move it back under review.
        app(KycService::class)->startReview($user);
        $this->assertSame(KycState::UnderReview, $user->fresh()->kyc_state);
    }

    // ----- 3. Notifications -----

    public function test_approval_sends_notification(): void
    {
        Notification::fake();
        $user = $this->member(KycState::UnderReview);

        app(KycService::class)->approve($user);

        Notification::assertSentTo($user, KycApprovedNotification::class);
    }

    public function test_rejection_sends_notification_with_reason(): void
    {
        Notification::fake();
        $user = $this->member(KycState::UnderReview);

        app(KycService::class)->reject($user, 'المستند غير مقروء');

        Notification::assertSentTo($user, fn (KycRejectedNotification $n): bool => $n->reason === 'المستند غير مقروء');
    }

    public function test_notifications_persist_to_database_with_content(): void
    {
        $user = $this->member(KycState::UnderReview);

        app(KycService::class)->reject($user, 'بيانات ناقصة');

        $notification = $user->notifications()->first();
        $this->assertSame('kyc_rejected', $notification->data['type']);
        $this->assertStringContainsString('بيانات ناقصة', $notification->data['body']);
    }

    // ----- 4. Dashboard KYC progress widget -----

    public function test_dashboard_shows_kyc_progress_widget_when_not_approved(): void
    {
        $this->actingAs($this->member(KycState::UnderReview))
            ->get('/portal')
            ->assertOk()
            ->assertSee('التحقق من هويتك')
            ->assertSee('عرض حالة التحقق');
    }

    public function test_dashboard_widget_offers_resubmit_for_rejected(): void
    {
        $this->actingAs($this->member(KycState::Rejected))
            ->get('/portal')
            ->assertOk()
            ->assertSee('إعادة رفع المستندات');
    }

    public function test_dashboard_hides_widget_when_approved(): void
    {
        $this->actingAs($this->member(KycState::Approved))
            ->get('/portal')
            ->assertOk()
            ->assertDontSee('التحقق من هويتك');
    }

    public function test_kyc_widget_adds_no_queries_to_dashboard(): void
    {
        $countFor = function (KycState $state): int {
            $user = $this->member($state);
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($user)->get('/portal')->assertOk();
            $n = count(DB::getQueryLog());
            DB::flushQueryLog();
            DB::disableQueryLog();
            $this->app['auth']->forgetGuards();

            return $n;
        };

        // Warm the (cached) settings read the portal layout performs, so neither
        // measurement below pays that one-off query and the comparison is fair.
        app(Settings::class)->all();

        $withWidget = $countFor(KycState::UnderReview);
        $withoutWidget = $countFor(KycState::Approved);

        $this->assertSame($withoutWidget, $withWidget); // the widget reads the loaded user → 0 extra queries
    }
}
