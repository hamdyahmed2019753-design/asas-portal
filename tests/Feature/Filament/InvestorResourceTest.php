<?php

namespace Tests\Feature\Filament;

use App\Enums\KycState;
use App\Enums\KycStatus;
use App\Filament\Resources\InvestorResource\Pages\ListInvestors;
use App\Filament\Resources\InvestorResource\Pages\ViewInvestor;
use App\Filament\Resources\InvestorResource\RelationManagers\ContractInterestsRelationManager;
use App\Filament\Resources\InvestorResource\RelationManagers\InvestmentsRelationManager;
use App\Filament\Resources\InvestorResource\Widgets\InvestorStats;
use App\Models\Contract;
use App\Models\ContractInterest;
use App\Models\Investment;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvestorResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $this->admin = User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');

        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function makeUser(string $role, string $kyc = 'verified'): User
    {
        $user = User::forceCreate([
            'name' => 'User '.uniqid(),
            'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
            'kyc_status' => $kyc,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_it_lists_investors_and_members_but_not_admins(): void
    {
        $investor = $this->makeUser('investor');
        $member = $this->makeUser('member');

        Livewire::test(ListInvestors::class)
            ->assertCanSeeTableRecords([$investor, $member])
            ->assertCanNotSeeTableRecords([$this->admin]);
    }

    public function test_admin_can_approve_kyc_via_table_action(): void
    {
        $investor = $this->makeUser('investor', 'pending');
        $investor->forceFill(['kyc_state' => KycState::UnderReview->value])->save();

        Livewire::test(ListInvestors::class)
            ->callTableAction('approveKyc', $investor)
            ->assertHasNoTableActionErrors();

        $investor->refresh();
        $this->assertSame(KycState::Approved, $investor->kyc_state);
        $this->assertSame(KycStatus::Verified, $investor->kyc_status);
    }

    public function test_admin_can_reject_kyc_with_mandatory_reason(): void
    {
        $investor = $this->makeUser('investor', 'pending');
        $investor->forceFill(['kyc_state' => KycState::UnderReview->value])->save();

        // Reason is required.
        Livewire::test(ListInvestors::class)
            ->callTableAction('rejectKyc', $investor, data: ['reason' => ''])
            ->assertHasTableActionErrors(['reason']);

        Livewire::test(ListInvestors::class)
            ->callTableAction('rejectKyc', $investor, data: ['reason' => 'مستند غير مقروء'])
            ->assertHasNoTableActionErrors();

        $investor->refresh();
        $this->assertSame(KycState::Rejected, $investor->kyc_state);
        $this->assertSame('مستند غير مقروء', $investor->kyc_rejection_reason);
    }

    public function test_it_can_filter_by_kyc_status(): void
    {
        $verified = $this->makeUser('investor', 'verified');
        $pending = $this->makeUser('investor', 'pending');

        Livewire::test(ListInvestors::class)
            ->filterTable('kyc_status', 'verified')
            ->assertCanSeeTableRecords([$verified])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_it_can_filter_by_role(): void
    {
        $investor = $this->makeUser('investor');
        $member = $this->makeUser('member');

        Livewire::test(ListInvestors::class)
            ->filterTable('role', 'member')
            ->assertCanSeeTableRecords([$member])
            ->assertCanNotSeeTableRecords([$investor]);
    }

    public function test_stats_widget_renders_the_four_kpis(): void
    {
        $this->makeUser('investor');
        $this->makeUser('member');

        Livewire::test(InvestorStats::class)
            ->assertSuccessful()
            ->assertSee('عدد المستثمرين')
            ->assertSee('عدد الأعضاء')
            ->assertSee('KYC موثّق')
            ->assertSee('KYC قيد المراجعة');
    }

    public function test_investments_relation_manager_renders(): void
    {
        $investor = $this->makeUser('investor');
        $contract = Contract::create([
            'title' => 'عقد', 'activity_type' => 'تجارة', 'target_amount' => 100000,
            'min_amount' => 1000, 'duration_months' => 12, 'payouts_count' => 4, 'status' => 'open',
        ]);
        Investment::create([
            'user_id' => $investor->id, 'contract_id' => $contract->id,
            'amount' => 5000, 'status' => 'approved',
        ]);

        Livewire::test(InvestmentsRelationManager::class, [
            'ownerRecord' => $investor,
            'pageClass' => ViewInvestor::class,
        ])->assertSuccessful();
    }

    public function test_contract_interests_relation_manager_renders(): void
    {
        $investor = $this->makeUser('investor');
        $contract = Contract::create([
            'title' => 'عقد', 'activity_type' => 'تجارة', 'target_amount' => 100000,
            'min_amount' => 1000, 'duration_months' => 12, 'payouts_count' => 4, 'status' => 'upcoming',
        ]);
        ContractInterest::create(['user_id' => $investor->id, 'contract_id' => $contract->id]);

        Livewire::test(ContractInterestsRelationManager::class, [
            'ownerRecord' => $investor,
            'pageClass' => ViewInvestor::class,
        ])->assertSuccessful();
    }
}
