<?php

namespace Tests\Feature;

use App\Actions\ContractInterests\ConvertContractInterest;
use App\Enums\ContractInterestStatus;
use App\Enums\KycState;
use App\Filament\Resources\ContractInterestResource;
use App\Models\Contract;
use App\Models\ContractInterest;
use App\Models\Investment;
use App\Models\User;
use App\Notifications\ContractInterestNotification;
use App\Services\Portal\ContractInterestService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PortalContractInterestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(?KycState $state = null): User
    {
        $user = User::forceCreate([
            'name' => 'مستثمر', 'email' => uniqid('u_').'@test.local', 'password' => 'secret123', 'email_verified_at' => now(),
            'kyc_state' => $state?->value,
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function approved(): User
    {
        return $this->member(KycState::Approved);
    }

    private function contract(): Contract
    {
        return Contract::create([
            'title' => 'صندوق النمو', 'activity_type' => 'تجارة', 'expected_return' => 12,
            'target_amount' => 1_000_000, 'min_amount' => 5_000, 'duration_months' => 12,
            'payouts_count' => 4, 'status' => 'open',
        ]);
    }

    // ----- Investor -----

    public function test_approved_investor_can_submit_interest(): void
    {
        Notification::fake();
        $user = $this->approved();
        $contract = $this->contract();

        $this->actingAs($user)
            ->post(route('portal.contracts.interest', $contract), ['notes' => 'مهتم جدًا', 'confirm' => '1'])
            ->assertRedirect();

        $interest = $user->contractInterests()->first();
        $this->assertNotNull($interest);
        $this->assertSame(ContractInterestStatus::Pending, $interest->status);
        $this->assertSame('مهتم جدًا', $interest->notes);
        Notification::assertSentTo($user, ContractInterestNotification::class);
    }

    public function test_interest_modal_has_no_click_outside_regression(): void
    {
        // Regression: the interest modal must NOT use @click.outside on .ip-modal
        // (it races with the opening click and instantly closes the modal — the
        // "nothing happens" bug). The full-screen backdrop handles outside clicks.
        $contract = $this->contract();

        $this->actingAs($this->approved())
            ->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('إبداء اهتمام')
            ->assertSee('/portal/contracts/'.$contract->id.'/interest', false)
            ->assertSee('class="ip-modal"', false)
            ->assertDontSee('ip-modal" @click.outside', false);
    }

    public function test_already_invested_user_sees_participation_not_interest_button(): void
    {
        $user = $this->approved();
        $contract = $this->contract();
        Investment::create([
            'user_id' => $user->id, 'contract_id' => $contract->id,
            'amount' => 25000, 'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('أنت مشارك في هذا العقد')
            ->assertDontSee('إبداء اهتمام');
    }

    public function test_interest_requires_confirmation(): void
    {
        $user = $this->approved();
        $contract = $this->contract();

        $this->actingAs($user)
            ->post(route('portal.contracts.interest', $contract), ['notes' => 'بدون تأكيد'])
            ->assertSessionHasErrors('confirm');

        $this->assertSame(0, $user->contractInterests()->count());
    }

    public function test_non_approved_kyc_cannot_submit_interest(): void
    {
        $user = $this->member(KycState::UnderReview);
        $contract = $this->contract();

        $this->actingAs($user)
            ->post(route('portal.contracts.interest', $contract), ['confirm' => '1'])
            ->assertForbidden();

        $this->assertSame(0, $user->contractInterests()->count());
    }

    public function test_duplicate_active_interest_is_blocked(): void
    {
        $user = $this->approved();
        $contract = $this->contract();
        app(ContractInterestService::class)->express($user, $contract);

        $this->actingAs($user)
            ->post(route('portal.contracts.interest', $contract), ['confirm' => '1'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, $user->contractInterests()->where('contract_id', $contract->id)->count());
    }

    public function test_rejected_interest_can_be_re_expressed(): void
    {
        $user = $this->approved();
        $contract = $this->contract();
        $first = app(ContractInterestService::class)->express($user, $contract);
        app(ContractInterestService::class)->reject($first);

        // A new interest is allowed once the previous one is closed.
        $second = app(ContractInterestService::class)->express($user, $contract);

        $this->assertSame(ContractInterestStatus::Pending, $second->status);
        $this->assertSame(2, $user->contractInterests()->where('contract_id', $contract->id)->count());
    }

    // ----- Admin transitions -----

    public function test_admin_mark_contacted(): void
    {
        Notification::fake();
        $user = $this->approved();
        $interest = app(ContractInterestService::class)->express($user, $this->contract());

        app(ContractInterestService::class)->markContacted($interest);

        $interest->refresh();
        $this->assertSame(ContractInterestStatus::Contacted, $interest->status);
        $this->assertNotNull($interest->contacted_at);
    }

    public function test_admin_convert_creates_pending_investment(): void
    {
        Notification::fake();
        $user = $this->approved();
        $contract = $this->contract();
        $interest = app(ContractInterestService::class)->express($user, $contract);

        $investment = app(ConvertContractInterest::class)->execute($interest, 25000);

        $this->assertInstanceOf(Investment::class, $investment);
        $this->assertSame($user->id, $investment->user_id);
        $this->assertSame($contract->id, $investment->contract_id);
        $this->assertSame('25000.00', (string) $investment->amount);
        $this->assertSame(ContractInterestStatus::Converted, $interest->refresh()->status);
        $this->assertNotNull($interest->converted_at);
    }

    public function test_admin_reject(): void
    {
        Notification::fake();
        $interest = app(ContractInterestService::class)->express($this->approved(), $this->contract());

        app(ContractInterestService::class)->reject($interest);

        $this->assertSame(ContractInterestStatus::Rejected, $interest->refresh()->status);
    }

    // ----- Security / ownership -----

    public function test_investor_sees_only_own_interests(): void
    {
        $userA = $this->approved();
        $userB = $this->approved();
        $contract = $this->contract();
        $a = app(ContractInterestService::class)->express($userA, $contract);

        $this->assertTrue($userA->can('view', $a));
        $this->assertFalse($userB->can('view', $a));

        // Scoped read returns only the owner's interest.
        $this->assertNull(app(ContractInterestService::class)->forContract($userB, $contract));
        $this->assertNotNull(app(ContractInterestService::class)->forContract($userA, $contract));
    }

    public function test_only_admin_can_view_any_interests(): void
    {
        $admin = $this->member();
        $admin->syncRoles('admin');
        $member = $this->approved();

        $this->assertTrue($admin->can('viewAny', ContractInterest::class));
        $this->assertFalse($member->can('viewAny', ContractInterest::class));
    }

    // ----- Performance -----

    public function test_admin_listing_does_not_trigger_n_plus_one(): void
    {
        for ($i = 0; $i < 10; $i++) {
            app(ContractInterestService::class)->express($this->approved(), $this->contract());
        }

        DB::enableQueryLog();
        ContractInterestResource::getEloquentQuery()
            ->get()
            ->each(fn (ContractInterest $i) => $i->user?->name.$i->contract?->title);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        // interests + users + contracts (eager) — constant, no per-row queries.
        $this->assertLessThan(5, $count);
    }

    public function test_pending_count_for_dashboard(): void
    {
        $user = $this->approved();
        app(ContractInterestService::class)->express($user, $this->contract());
        app(ContractInterestService::class)->express($user, $this->contract());

        $this->assertSame(2, app(ContractInterestService::class)->pendingCount($user));
    }
}
