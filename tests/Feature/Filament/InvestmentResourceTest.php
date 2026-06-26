<?php

namespace Tests\Feature\Filament;

use App\Actions\Investments\ApproveInvestment;
use App\Actions\Investments\RejectInvestment;
use App\Enums\InvestmentStatus;
use App\Filament\Resources\InvestmentResource\Pages\ListInvestments;
use App\Filament\Resources\InvestmentResource\Pages\ViewInvestment;
use App\Filament\Resources\InvestmentResource\RelationManagers\PayoutsRelationManager;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvestmentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function pendingInvestment(): Investment
    {
        $user = User::create([
            'name' => 'Member',
            'email' => uniqid('member_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);

        $contract = Contract::create([
            'title' => 'عقد',
            'activity_type' => 'تجارة',
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => 'open',
        ]);

        return Investment::create([
            'user_id' => $user->id,
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'pending',
        ]);
    }

    private function approvedInvestment(): Investment
    {
        $investment = $this->pendingInvestment();
        app(ApproveInvestment::class)->execute($investment);

        return $investment->refresh();
    }

    private function rejectedInvestment(): Investment
    {
        $investment = $this->pendingInvestment();
        app(RejectInvestment::class)->execute($investment, 'سبب');

        return $investment->refresh();
    }

    public function test_approve_action_visible_only_when_pending(): void
    {
        Livewire::test(ListInvestments::class)
            ->assertTableActionVisible('approve', $this->pendingInvestment())
            ->assertTableActionHidden('approve', $this->approvedInvestment());
    }

    public function test_reject_action_visible_only_when_pending(): void
    {
        Livewire::test(ListInvestments::class)
            ->assertTableActionVisible('reject', $this->pendingInvestment())
            ->assertTableActionHidden('reject', $this->rejectedInvestment());
    }

    public function test_it_can_approve_an_investment(): void
    {
        $investment = $this->pendingInvestment();

        Livewire::test(ListInvestments::class)
            ->callTableAction('approve', $investment);

        $investment->refresh();
        $this->assertSame(InvestmentStatus::Approved, $investment->status);
        $this->assertSame(5, $investment->payouts()->count());
        $this->assertTrue($investment->user->fresh()->hasRole('investor'));
    }

    public function test_it_can_reject_an_investment_with_reason(): void
    {
        $investment = $this->pendingInvestment();

        Livewire::test(ListInvestments::class)
            ->callTableAction('reject', $investment, data: [
                'rejection_reason' => 'المبلغ غير مكتمل',
            ]);

        $investment->refresh();
        $this->assertSame(InvestmentStatus::Rejected, $investment->status);
        $this->assertSame('المبلغ غير مكتمل', $investment->rejection_reason);
        $this->assertNotNull($investment->rejected_at);
    }

    public function test_approve_is_hidden_after_approval_preventing_re_approval(): void
    {
        Livewire::test(ListInvestments::class)
            ->assertTableActionHidden('approve', $this->approvedInvestment());
    }

    public function test_edit_is_hidden_after_approval(): void
    {
        Livewire::test(ListInvestments::class)
            ->assertTableActionHidden('edit', $this->approvedInvestment());
    }

    public function test_edit_is_hidden_after_rejection(): void
    {
        Livewire::test(ListInvestments::class)
            ->assertTableActionHidden('edit', $this->rejectedInvestment());
    }

    public function test_edit_is_visible_while_pending(): void
    {
        Livewire::test(ListInvestments::class)
            ->assertTableActionVisible('edit', $this->pendingInvestment());
    }

    public function test_payouts_relation_manager_renders(): void
    {
        $investment = $this->approvedInvestment();

        Livewire::test(PayoutsRelationManager::class, [
            'ownerRecord' => $investment,
            'pageClass' => ViewInvestment::class,
        ])->assertSuccessful();
    }
}
