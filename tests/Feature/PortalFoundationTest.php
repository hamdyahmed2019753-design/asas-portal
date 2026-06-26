<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $role.'_'.uniqid().'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_public_pages_are_accessible_to_guests(): void
    {
        $this->get('/')->assertOk();
        $this->get('/contracts')->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_portal(): void
    {
        $this->get('/portal')->assertRedirect(route('login'));
    }

    public function test_admin_is_redirected_to_the_admin_panel_from_portal(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get('/portal')
            ->assertRedirect('/admin');
    }

    public function test_investor_can_access_the_portal(): void
    {
        $this->actingAs($this->userWithRole('investor'))
            ->get('/portal')
            ->assertOk()
            ->assertSee('لوحتي');
    }

    public function test_member_can_access_the_portal(): void
    {
        $this->actingAs($this->userWithRole('member'))
            ->get('/portal')
            ->assertOk();
    }

    public function test_dashboard_route_forwards_to_the_portal(): void
    {
        $this->actingAs($this->userWithRole('investor'))
            ->get('/dashboard')
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_all_portal_pages_render_for_an_investor(): void
    {
        $investor = $this->userWithRole('investor');

        $routes = ['portal.portfolio', 'portal.investments', 'portal.payouts', 'portal.contracts', 'portal.news', 'portal.profile', 'portal.notifications'];

        foreach ($routes as $route) {
            $this->actingAs($investor)->get(route($route))->assertOk();
        }
    }

    public function test_portal_contracts_shows_available_contracts_not_a_placeholder(): void
    {
        Contract::create([
            'title' => 'صندوق النمو العقاري', 'activity_type' => 'عقارات', 'expected_return' => 14,
            'target_amount' => 750000, 'min_amount' => 10000, 'duration_months' => 12,
            'payouts_count' => 4, 'status' => 'open',
        ]);

        $this->actingAs($this->userWithRole('investor'))
            ->get(route('portal.contracts'))
            ->assertOk()
            ->assertSee('العقود الاستثمارية')
            ->assertSee('صندوق النمو العقاري')
            ->assertDontSee('Coming Soon');
    }
}
