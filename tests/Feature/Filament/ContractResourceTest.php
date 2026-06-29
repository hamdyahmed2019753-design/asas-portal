<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ContractResource\Pages\CreateContract;
use App\Filament\Resources\ContractResource\Pages\EditContract;
use App\Filament\Resources\ContractResource\Pages\ListContracts;
use App\Filament\Resources\ContractResource\Pages\ViewContract;
use App\Filament\Resources\ContractResource\RelationManagers\ContractInterestsRelationManager;
use App\Filament\Resources\ContractResource\RelationManagers\InvestmentsRelationManager;
use App\Models\Contract;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $admin = User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function contract(string $status = 'open', string $activity = 'تجارة'): Contract
    {
        return Contract::create([
            'title' => 'عقد '.uniqid(),
            'activity_type' => $activity,
            'target_amount' => 100000,
            'min_amount' => 1000,
            'max_amount' => 50000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => $status,
        ]);
    }

    public function test_it_renders_the_list_page(): void
    {
        $this->contract();

        Livewire::test(ListContracts::class)->assertSuccessful();
    }

    public function test_it_can_create_a_contract(): void
    {
        Livewire::test(CreateContract::class)
            ->fillForm([
                'title' => 'صندوق النمو',
                'activity_type' => 'تجارة',
                'status' => 'open',
                'target_amount' => 250000,
                'min_amount' => 5000,
                'max_amount' => 100000,
                'expected_return' => 12,
                'duration_months' => 12,
                'payouts_count' => 4,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contracts', [
            'title' => 'صندوق النمو',
            'status' => 'open',
            'payouts_count' => 4,
        ]);
    }

    public function test_it_can_edit_a_contract(): void
    {
        $contract = $this->contract();

        Livewire::test(EditContract::class, ['record' => $contract->getRouteKey()])
            ->fillForm([
                'title' => 'اسم محدّث',
                'status' => 'running',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('اسم محدّث', $contract->refresh()->title);
        $this->assertSame('running', $contract->status->value);
    }

    public function test_it_can_delete_a_contract(): void
    {
        $contract = $this->contract();

        Livewire::test(ListContracts::class)
            ->callTableAction('delete', $contract);

        $this->assertModelMissing($contract);
    }

    public function test_it_can_filter_by_status(): void
    {
        $open = $this->contract('open');
        $closed = $this->contract('closed');

        Livewire::test(ListContracts::class)
            ->filterTable('status', 'open')
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$closed]);
    }

    public function test_it_can_filter_by_activity_type(): void
    {
        $trade = $this->contract('open', 'تجارة');
        $realEstate = $this->contract('open', 'عقار');

        Livewire::test(ListContracts::class)
            ->filterTable('activity_type', 'عقار')
            ->assertCanSeeTableRecords([$realEstate])
            ->assertCanNotSeeTableRecords([$trade]);
    }

    public function test_investments_relation_manager_renders(): void
    {
        $contract = $this->contract();

        Livewire::test(InvestmentsRelationManager::class, [
            'ownerRecord' => $contract,
            'pageClass' => ViewContract::class,
        ])->assertSuccessful();
    }

    public function test_contract_interests_relation_manager_renders(): void
    {
        $contract = $this->contract();

        Livewire::test(ContractInterestsRelationManager::class, [
            'ownerRecord' => $contract,
            'pageClass' => ViewContract::class,
        ])->assertSuccessful();
    }
}
