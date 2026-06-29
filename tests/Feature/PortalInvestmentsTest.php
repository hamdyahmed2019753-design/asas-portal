<?php

namespace Tests\Feature;

use App\Actions\Investments\ApproveInvestment;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use App\Services\Portal\InvestmentPortalService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalInvestmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function member(): User
    {
        $user = User::forceCreate([
            'name' => 'مستثمر',
            'email' => uniqid('u_').'@test.local',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);
        $user->assignRole('member');

        return $user;
    }

    private function contract(?string $title = null): Contract
    {
        return Contract::create([
            'title' => $title ?? ('عقد '.uniqid()),
            'activity_type' => 'تجارة',
            'expected_return' => 12,
            'target_amount' => 1_000_000,
            'min_amount' => 1_000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => 'open',
        ]);
    }

    private function investment(User $user, ?Contract $contract = null, string $status = 'pending', float $amount = 5000): Investment
    {
        return Investment::create([
            'user_id' => $user->id,
            'contract_id' => ($contract ?? $this->contract())->id,
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    /** Approved investment with payouts (one paid). */
    private function approvedInvestment(User $user): Investment
    {
        $investment = $this->investment($user);
        app(ApproveInvestment::class)->execute($investment);

        $payout = $investment->payouts()->where('type', 'profit')->orderBy('sequence')->first();
        $payout->update(['amount' => 1500, 'status' => 'paid', 'paid_at' => now()]);

        return $investment->refresh();
    }

    public function test_investments_list_works(): void
    {
        $user = $this->member();
        $this->investment($user);

        $this->actingAs($user)->get('/portal/investments')->assertOk()->assertSee('مشاركاتي');
    }

    public function test_list_is_paginated_at_10(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 11; $i++) {
            $this->investment($user);
        }

        $data = app(InvestmentPortalService::class)->list($user, Request::create('/'));

        $this->assertSame(10, $data['investments']->count());
        $this->assertTrue($data['investments']->hasMorePages());
    }

    public function test_filter_by_status(): void
    {
        $user = $this->member();
        $approved = $this->investment($user, status: 'approved');
        $pending = $this->investment($user, status: 'pending');

        $data = app(InvestmentPortalService::class)->list($user, Request::create('/', 'GET', ['status' => 'approved']));

        $this->assertTrue($data['investments']->contains($approved));
        $this->assertFalse($data['investments']->contains($pending));
    }

    public function test_filter_by_contract(): void
    {
        $user = $this->member();
        $c1 = $this->contract();
        $c2 = $this->contract();
        $inv1 = $this->investment($user, $c1);
        $inv2 = $this->investment($user, $c2);

        $data = app(InvestmentPortalService::class)->list($user, Request::create('/', 'GET', ['contract' => $c1->id]));

        $this->assertTrue($data['investments']->contains($inv1));
        $this->assertFalse($data['investments']->contains($inv2));
    }

    public function test_filter_active_only(): void
    {
        $user = $this->member();
        $approved = $this->investment($user, status: 'approved');
        $pending = $this->investment($user, status: 'pending');

        $data = app(InvestmentPortalService::class)->list($user, Request::create('/', 'GET', ['active' => '1']));

        $this->assertTrue($data['investments']->contains($approved));
        $this->assertFalse($data['investments']->contains($pending));
    }

    public function test_details_page_works_for_owner(): void
    {
        $user = $this->member();
        $investment = $this->approvedInvestment($user);

        $this->actingAs($user)
            ->get(route('portal.investments.show', $investment))
            ->assertOk()
            ->assertSee('تفاصيل المشاركة');
    }

    public function test_hero_and_summary_data_are_correct(): void
    {
        $user = $this->member();
        $investment = $this->approvedInvestment($user);

        $data = app(InvestmentPortalService::class)->details($investment);

        $this->assertSame(5000.0, $data['investedAmount']);
        $this->assertSame(1500.0, $data['profitPaid']);
        $this->assertSame(5, $data['summary']['total']);   // 4 profit + 1 capital
        $this->assertSame(1, $data['summary']['paid']);
    }

    public function test_payouts_are_loaded(): void
    {
        $user = $this->member();
        $investment = $this->approvedInvestment($user);

        $data = app(InvestmentPortalService::class)->details($investment);

        $this->assertTrue($data['hasPayouts']);
        $this->assertCount(5, $data['payouts']);
    }

    public function test_timeline_includes_creation_and_approval(): void
    {
        $user = $this->member();
        $investment = $this->approvedInvestment($user);

        $data = app(InvestmentPortalService::class)->details($investment);
        $titles = array_column($data['timeline'], 'title');

        $this->assertContains('تم تقديم المشاركة', $titles);
        $this->assertContains('تم اعتماد المشاركة', $titles);
        $this->assertContains('أول توزيعة مدفوعة', $titles);
    }

    public function test_profit_breakdown(): void
    {
        $user = $this->member();
        $investment = $this->approvedInvestment($user); // capital 5000, one 1500 profit paid

        $profit = app(InvestmentPortalService::class)->details($investment)['profit'];

        $this->assertSame(1500.0, $profit['received']);
        $this->assertSame(0.0, $profit['remaining']);   // no other profit amount is set yet
        $this->assertSame(1500.0, $profit['expected']);
        $this->assertSame(6500.0, $profit['value']);    // capital 5000 + realized 1500
    }

    public function test_timeline_includes_interest_and_contract_end(): void
    {
        $user = $this->member();
        $contract = $this->contract();
        \App\Models\ContractInterest::create([
            'user_id' => $user->id, 'contract_id' => $contract->id, 'status' => 'pending',
        ]);
        $investment = $this->investment($user, $contract);
        app(ApproveInvestment::class)->execute($investment);

        $titles = array_column(app(InvestmentPortalService::class)->details($investment->refresh())['timeline'], 'title');

        $this->assertContains('تم إرسال الاهتمام', $titles);
        $this->assertContains('نهاية العقد المتوقعة', $titles); // end date is in the future
    }

    public function test_empty_state_when_no_payouts(): void
    {
        $user = $this->member();
        $investment = $this->investment($user); // pending → no payouts

        $this->actingAs($user)
            ->get(route('portal.investments.show', $investment))
            ->assertOk()
            ->assertSee('لا توجد توزيعات بعد');
    }

    public function test_ownership_policy(): void
    {
        $owner = $this->member();
        $other = $this->member();
        $investment = $this->investment($owner);

        $this->assertTrue($owner->can('view', $investment));
        $this->assertFalse($other->can('view', $investment));
    }

    public function test_idor_direct_url_is_blocked(): void
    {
        $owner = $this->member();
        $attacker = $this->member();
        $investment = $this->investment($owner);

        $this->actingAs($attacker)
            ->get(route('portal.investments.show', $investment))
            ->assertForbidden();
    }

    public function test_list_does_not_trigger_n_plus_one(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 8; $i++) {
            $this->approvedInvestment($user);
        }

        DB::enableQueryLog();
        $this->actingAs($user->refresh())->get('/portal/investments')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $queryCount);
    }
}
