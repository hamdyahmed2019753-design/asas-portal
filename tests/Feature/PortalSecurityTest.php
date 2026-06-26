<?php

namespace Tests\Feature;

use App\Enums\DocumentCategory;
use App\Enums\KycState;
use App\Models\User;
use App\Models\UserLogin;
use App\Services\Portal\AccountSecurityService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalSecurityTest extends TestCase
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
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
        ], $attributes));
        $user->assignRole('member');

        return $user;
    }

    public function test_sensitive_fields_are_not_mass_assignable(): void
    {
        // Hardening (O3): KYC / onboarding / 2FA fields must NOT be fillable,
        // so a crafted payload cannot self-approve KYC or bypass onboarding.
        $user = (new User)->fill([
            'name' => 'محاول',
            'email' => 'guard@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
            'kyc_state' => KycState::Approved->value,
            'kyc_status' => 'verified',
            'onboarding_completed_at' => now(),
            'two_factor_enabled' => true,
            'identity_document_path' => 'hacked.pdf',
        ]);

        $this->assertNull($user->kyc_state);
        $this->assertNull($user->kyc_status);
        $this->assertNull($user->onboarding_completed_at);
        $this->assertNull($user->identity_document_path);
        $this->assertNotTrue($user->two_factor_enabled);
        // …but the legitimate user-editable fields ARE assigned.
        $this->assertSame('محاول', $user->name);
    }

    public function test_settings_page_renders_for_member(): void
    {
        $this->actingAs($this->member())->get('/portal/settings')->assertOk()->assertSee('الإعدادات والأمان');
    }

    public function test_guest_cannot_view_settings(): void
    {
        $this->get('/portal/settings')->assertRedirect(route('login'));
    }

    public function test_logout_others_modal_has_no_click_outside_regression(): void
    {
        // Same modal-race regression guard as the contract-interest modal.
        $user = $this->member(['phone' => '055']);
        // Seed a second session so the "logout others" modal trigger renders.
        DB::table(config('session.table', 'sessions'))->insert([
            'id' => 'sess-a', 'user_id' => $user->id, 'ip_address' => '1.1.1.1',
            'user_agent' => 'UA', 'payload' => 'x', 'last_activity' => time(),
        ]);
        DB::table(config('session.table', 'sessions'))->insert([
            'id' => 'sess-b', 'user_id' => $user->id, 'ip_address' => '2.2.2.2',
            'user_agent' => 'UA', 'payload' => 'x', 'last_activity' => time(),
        ]);

        $this->actingAs($user)
            ->get('/portal/settings')
            ->assertOk()
            ->assertSee('تسجيل الخروج من الأجهزة الأخرى')
            ->assertSee('class="ip-modal"', false)
            ->assertDontSee('ip-modal" @click.outside', false);
    }

    public function test_update_profile(): void
    {
        $user = $this->member();

        $this->actingAs($user)->patch(route('portal.settings.profile'), [
            'name' => 'خالد العتيبي', 'phone' => '0509998888', 'city' => 'جدة', 'country' => 'السعودية',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('خالد العتيبي', $user->name);
        $this->assertSame('0509998888', $user->phone);
        $this->assertSame('جدة', $user->city);
        $this->assertSame('السعودية', $user->country);
    }

    public function test_email_is_not_changed_by_profile_update(): void
    {
        $user = $this->member();
        $original = $user->email;

        $this->actingAs($user)->patch(route('portal.settings.profile'), [
            'name' => 'اسم', 'email' => 'hacker@evil.test',
        ]);

        $this->assertSame($original, $user->fresh()->email);
    }

    public function test_change_password(): void
    {
        $user = $this->member();

        $this->actingAs($user)->put(route('portal.settings.password'), [
            'current_password' => 'secret123',
            'password' => 'NewStrongPass123',
            'password_confirmation' => 'NewStrongPass123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('NewStrongPass123', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->member();

        $this->actingAs($user)->put(route('portal.settings.password'), [
            'current_password' => 'wrong-password',
            'password' => 'NewStrongPass123',
            'password_confirmation' => 'NewStrongPass123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
    }

    public function test_logout_other_sessions(): void
    {
        $user = $this->member();
        $table = config('session.table', 'sessions');

        foreach (['current-sess', 'other-1', 'other-2'] as $id) {
            DB::table($table)->insert([
                'id' => $id, 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
                'user_agent' => 'Test', 'payload' => 'x', 'last_activity' => time(),
            ]);
        }

        app(AccountSecurityService::class)->logoutOtherSessions($user, 'current-sess');

        $remaining = DB::table($table)->where('user_id', $user->id)->pluck('id')->all();
        $this->assertSame(['current-sess'], $remaining);
    }

    public function test_every_login_is_recorded(): void
    {
        $user = $this->member();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertRedirect();

        $this->assertSame(1, UserLogin::where('user_id', $user->id)->count());
    }

    public function test_login_history_renders(): void
    {
        $user = $this->member();
        UserLogin::create([
            'user_id' => $user->id, 'ip_address' => '203.0.113.7',
            'user_agent' => 'Mozilla/5.0 Chrome', 'logged_in_at' => now(),
        ]);

        $this->actingAs($user)->get('/portal/settings')->assertOk()->assertSee('203.0.113.7');
    }

    public function test_security_score(): void
    {
        $service = app(AccountSecurityService::class);

        // Bare account: only "password set" → 20.
        $this->assertSame(20, $service->securityScore($this->member())['score']);

        // Fully secured account → 100.
        $full = $this->member([
            'phone' => '055', 'onboarding_completed_at' => now(), 'kyc_state' => KycState::Approved->value,
        ]);
        $full->documents()->create([
            'category' => DocumentCategory::Kyc->value, 'title' => 'الهوية',
            'disk' => 'local', 'path' => 'a.pdf', 'size' => 1,
        ]);

        $score = $service->securityScore($full);
        $this->assertSame(100, $score['score']);
        $this->assertSame('ممتاز', $score['status']);
    }

    public function test_login_history_is_scoped_to_owner(): void
    {
        $userA = $this->member();
        UserLogin::create(['user_id' => $userA->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'A', 'logged_in_at' => now()]);
        $userB = $this->member();
        UserLogin::create(['user_id' => $userB->id, 'ip_address' => '2.2.2.2', 'user_agent' => 'B', 'logged_in_at' => now()]);

        $historyB = app(AccountSecurityService::class)->loginHistory($userB);
        $this->assertCount(1, $historyB);
        $this->assertSame('2.2.2.2', $historyB->first()->ip_address);
    }

    public function test_settings_page_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 20; $i++) {
            UserLogin::create(['user_id' => $user->id, 'ip_address' => '1.1.1.1', 'user_agent' => 'UA', 'logged_in_at' => now()->subMinutes($i)]);
        }

        DB::enableQueryLog();
        $this->actingAs($user)->get('/portal/settings')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $count);
    }
}
