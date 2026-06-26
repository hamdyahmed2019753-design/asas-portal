<?php

namespace Tests\Feature\Filament;

use App\Enums\PayoutStatus;
use App\Filament\Resources\PayoutResource\Pages\EditPayout;
use App\Filament\Resources\PayoutResource\Pages\ListPayouts;
use App\Filament\Resources\PayoutResource\Widgets\PayoutStats;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PayoutResourceTest extends TestCase
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

    private function makePayout(string $type = 'profit', ?float $amount = 200, string $status = 'scheduled'): Payout
    {
        $user = User::create([
            'name' => 'Investor',
            'email' => uniqid('inv_').'@test.local',
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

        $investment = Investment::create([
            'user_id' => $user->id,
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'approved',
            'start_date' => Carbon::create(2026, 1, 1),
            'end_date' => Carbon::create(2027, 1, 1),
        ]);

        return Payout::create([
            'investment_id' => $investment->id,
            'type' => $type,
            'sequence' => $type === 'profit' ? 1 : null,
            'due_date' => Carbon::create(2026, 4, 1),
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    public function test_mark_as_paid_visible_when_not_paid(): void
    {
        Livewire::test(ListPayouts::class)
            ->assertTableActionVisible('markAsPaid', $this->makePayout('profit', 200, 'scheduled'));
    }

    public function test_mark_as_paid_hidden_after_paid(): void
    {
        Livewire::test(ListPayouts::class)
            ->assertTableActionHidden('markAsPaid', $this->makePayout('profit', 200, 'paid'));
    }

    public function test_it_can_pay_a_profit_payout(): void
    {
        $payout = $this->makePayout('profit', 200, 'scheduled');

        Livewire::test(ListPayouts::class)
            ->callTableAction('markAsPaid', $payout);

        $payout->refresh();
        $this->assertSame(PayoutStatus::Paid, $payout->status);
        $this->assertNotNull($payout->paid_at);
    }

    public function test_it_can_pay_a_capital_payout(): void
    {
        $payout = $this->makePayout('capital', 5000, 'scheduled');

        Livewire::test(ListPayouts::class)
            ->callTableAction('markAsPaid', $payout);

        $this->assertSame(PayoutStatus::Paid, $payout->refresh()->status);
    }

    public function test_it_does_not_pay_a_profit_payout_without_amount(): void
    {
        $payout = $this->makePayout('profit', null, 'scheduled');

        Livewire::test(ListPayouts::class)
            ->callTableAction('markAsPaid', $payout);

        $payout->refresh();
        $this->assertSame(PayoutStatus::Scheduled, $payout->status);
        $this->assertNull($payout->paid_at);
    }

    public function test_edit_is_hidden_after_paid(): void
    {
        Livewire::test(ListPayouts::class)
            ->assertTableActionHidden('edit', $this->makePayout('profit', 200, 'paid'));
    }

    public function test_capital_amount_field_is_disabled(): void
    {
        $payout = $this->makePayout('capital', 5000, 'scheduled');

        Livewire::test(EditPayout::class, ['record' => $payout->getRouteKey()])
            ->assertFormFieldIsDisabled('amount');
    }

    public function test_profit_amount_field_is_editable_while_unpaid(): void
    {
        $payout = $this->makePayout('profit', 200, 'scheduled');

        Livewire::test(EditPayout::class, ['record' => $payout->getRouteKey()])
            ->assertFormFieldIsEnabled('amount');
    }

    public function test_paid_payout_amount_field_is_disabled(): void
    {
        $payout = $this->makePayout('profit', 200, 'paid');

        Livewire::test(EditPayout::class, ['record' => $payout->getRouteKey()])
            ->assertFormFieldIsDisabled('amount');
    }

    public function test_it_can_filter_by_status(): void
    {
        $scheduled = $this->makePayout('profit', 200, 'scheduled');
        $paid = $this->makePayout('profit', 200, 'paid');

        Livewire::test(ListPayouts::class)
            ->filterTable('status', 'paid')
            ->assertCanSeeTableRecords([$paid])
            ->assertCanNotSeeTableRecords([$scheduled]);
    }

    public function test_it_can_filter_by_type(): void
    {
        $profit = $this->makePayout('profit', 200, 'scheduled');
        $capital = $this->makePayout('capital', 5000, 'scheduled');

        Livewire::test(ListPayouts::class)
            ->filterTable('type', 'capital')
            ->assertCanSeeTableRecords([$capital])
            ->assertCanNotSeeTableRecords([$profit]);
    }

    public function test_due_only_filter(): void
    {
        $due = $this->makePayout('profit', 200, 'due');
        $scheduled = $this->makePayout('profit', 200, 'scheduled');

        Livewire::test(ListPayouts::class)
            ->filterTable('due_only', true)
            ->assertCanSeeTableRecords([$due])
            ->assertCanNotSeeTableRecords([$scheduled]);
    }

    public function test_stats_widget_renders_the_four_kpis(): void
    {
        $this->makePayout('profit', 200, 'paid');

        Livewire::test(PayoutStats::class)
            ->assertSuccessful()
            ->assertSee('توزيعات مجدولة')
            ->assertSee('توزيعات مستحقة')
            ->assertSee('توزيعات مدفوعة')
            ->assertSee('إجمالي الأرباح المدفوعة');
    }
}
