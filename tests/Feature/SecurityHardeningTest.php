<?php

namespace Tests\Feature;

use App\Actions\Investments\ApproveInvestment;
use App\Filament\Resources\ContractResource\Pages\ListContracts;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
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

    public function test_security_headers_are_present_on_every_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_non_admin_cannot_open_a_resource_page_directly(): void
    {
        $this->actingAs($this->userWithRole('investor'));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListContracts::class)->assertForbidden();
    }

    public function test_approval_is_audited_with_causer_and_dirty_changes(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);

        $contract = Contract::create([
            'title' => 'عقد',
            'activity_type' => 'تجارة',
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => 'open',
        ]);
        $investment = Investment::create([
            'user_id' => $admin->id,
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'pending',
        ]);

        app(ApproveInvestment::class)->execute($investment);

        $activity = Activity::where('subject_type', Investment::class)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($admin->id, $activity->causer_id);              // من قام بالعملية
        $this->assertNotNull($activity->created_at);                      // متى تمت
        $this->assertArrayHasKey('status', $activity->properties['attributes'] ?? []); // التغييرات الفعلية
    }
}
