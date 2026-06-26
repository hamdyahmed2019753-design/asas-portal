<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(bool $verified): User
    {
        $user = $verified
            ? User::factory()->create()
            : User::factory()->unverified()->create();
        $user->assignRole('member');

        return $user;
    }

    public function test_unverified_member_is_blocked_from_the_portal(): void
    {
        $this->actingAs($this->member(verified: false))
            ->get('/portal')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_member_can_access_the_portal(): void
    {
        $this->actingAs($this->member(verified: true))
            ->get('/portal')
            ->assertOk()
            ->assertSee('لوحتي');
    }

    public function test_verification_notice_shows_alert_and_resend(): void
    {
        $this->actingAs($this->member(verified: false))
            ->get('/verify-email')
            ->assertOk()
            ->assertSee('وثّق بريدك الإلكتروني')
            ->assertSee('إعادة إرسال رسالة التحقق');
    }

    public function test_resend_sends_a_verification_email(): void
    {
        Notification::fake();
        $user = $this->member(verified: false);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
