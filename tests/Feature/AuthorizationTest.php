<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Investment;
use App\Models\NewsUpdate;
use App\Models\Payout;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::forceCreate([
            'name' => ucfirst($role),
            'email' => $role.'_'.uniqid().'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    // ----- Panel access -----

    public function test_admin_can_access_the_admin_panel(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_investor_cannot_access_the_admin_panel(): void
    {
        $this->actingAs($this->userWithRole('investor'))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_member_cannot_access_the_admin_panel(): void
    {
        $this->actingAs($this->userWithRole('member'))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_away_from_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_can_access_panel_method(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($this->userWithRole('admin')->canAccessPanel($panel));
        $this->assertFalse($this->userWithRole('investor')->canAccessPanel($panel));
        $this->assertFalse($this->userWithRole('member')->canAccessPanel($panel));
    }

    // ----- Policies -----

    public function test_admin_can_view_resources_but_non_admin_cannot(): void
    {
        $admin = $this->userWithRole('admin');
        $investor = $this->userWithRole('investor');

        $this->assertTrue($admin->can('viewAny', Contract::class));
        $this->assertTrue($admin->can('viewAny', NewsUpdate::class));
        $this->assertFalse($investor->can('viewAny', Contract::class));
        $this->assertFalse($investor->can('viewAny', Activity::class));
    }

    public function test_payouts_and_investments_cannot_be_deleted_even_by_admin(): void
    {
        $admin = $this->userWithRole('admin');

        $contract = Contract::create([
            'title' => 'عقد', 'activity_type' => 'تجارة', 'target_amount' => 100000,
            'min_amount' => 1000, 'duration_months' => 12, 'payouts_count' => 4, 'status' => 'open',
        ]);
        $investment = Investment::create([
            'user_id' => $admin->id, 'contract_id' => $contract->id,
            'amount' => 5000, 'status' => 'approved',
        ]);
        $payout = Payout::create([
            'investment_id' => $investment->id, 'type' => 'profit', 'sequence' => 1,
            'due_date' => Carbon::create(2026, 4, 1), 'amount' => 200, 'status' => 'scheduled',
        ]);

        $this->assertFalse($admin->can('delete', $investment));
        $this->assertFalse($admin->can('delete', $payout));
    }

    public function test_activity_log_is_read_only_even_for_admin(): void
    {
        $admin = $this->userWithRole('admin');
        $activity = activity()->log('test');

        $this->assertTrue($admin->can('view', $activity));
        $this->assertFalse($admin->can('delete', $activity));
        $this->assertFalse($admin->can('update', $activity));
        $this->assertFalse($admin->can('create', Activity::class));
    }

    public function test_admin_can_delete_news(): void
    {
        $admin = $this->userWithRole('admin');
        $news = NewsUpdate::create(['title' => 'خبر', 'body' => 'محتوى', 'is_published' => false]);

        $this->assertTrue($admin->can('delete', $news));
    }
}
