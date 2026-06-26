<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function userWith(string $role): User
    {
        $user = User::create([
            'name' => 'مستخدم',
            'email' => uniqid('u_').'@test.local',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole($role);

        return $user;
    }

    // ----- Page rendering (portal identity) -----

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('مرحبًا بعودتك')
            ->assertSee('ip-auth-card', false);
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('إنشاء حساب مستثمر');
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('نسيت كلمة المرور؟');
    }

    public function test_reset_password_page_renders(): void
    {
        $token = Password::createToken($this->userWith('member'));

        $this->get('/reset-password/'.$token)
            ->assertOk()
            ->assertSee('إعادة تعيين كلمة المرور');
    }

    // ----- Registration rules -----

    public function test_new_user_gets_member_role(): void
    {
        $this->post('/register', [
            'name' => 'مستثمر جديد',
            'email' => 'new@test.local',
            'phone' => '0551112233',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'new@test.local')->first();
        $this->assertTrue($user->hasRole('member'));
        $this->assertFalse($user->hasRole('investor'));
    }

    public function test_new_user_gets_pending_kyc(): void
    {
        $this->post('/register', [
            'name' => 'مستثمر جديد',
            'email' => 'kyc@test.local',
            'phone' => '0551112233',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertSame(KycStatus::Pending, User::where('email', 'kyc@test.local')->first()->kyc_status);
    }

    // ----- Role-based redirect after login -----

    public function test_admin_is_redirected_to_admin_panel(): void
    {
        $admin = $this->userWith('admin');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin');
    }

    public function test_investor_is_redirected_to_portal(): void
    {
        $investor = $this->userWith('investor');

        $this->post('/login', ['email' => $investor->email, 'password' => 'password'])
            ->assertRedirect('/portal');
    }

    public function test_member_is_redirected_to_portal(): void
    {
        $member = $this->userWith('member');

        $this->post('/login', ['email' => $member->email, 'password' => 'password'])
            ->assertRedirect('/portal');
    }
}
