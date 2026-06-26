<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\NewsUpdate;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class); // DashboardMetrics uses the investor role scope.
    }

    private function contract(string $status = 'open', string $activity = 'تجارة', ?string $title = null): Contract
    {
        return Contract::create([
            'title' => $title ?? ('عقد '.uniqid()),
            'activity_type' => $activity,
            'expected_return' => 12,
            'target_amount' => 500000,
            'min_amount' => 5000,
            'max_amount' => 100000,
            'duration_months' => 12,
            'payouts_count' => 4,
            'status' => $status,
        ]);
    }

    public function test_landing_page_works(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('استثمر بثقة مع أساس')
            ->assertSee('استعرض العقود');
    }

    public function test_contracts_page_works(): void
    {
        $this->contract('open');

        $this->get('/contracts')
            ->assertOk()
            ->assertSee('العقود الاستثمارية');
    }

    public function test_public_visible_contract_details_work(): void
    {
        $contract = $this->contract('open', title: 'صندوق النمو ٢');

        $this->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('صندوق النمو ٢')
            ->assertSee('تفاصيل الفرصة');
    }

    public function test_non_public_visible_contract_returns_404(): void
    {
        foreach (['running', 'closed', 'finished'] as $status) {
            $contract = $this->contract($status);
            $this->get(route('contracts.show', $contract))->assertNotFound();
        }
    }

    public function test_contracts_are_paginated_at_12_per_page(): void
    {
        for ($i = 0; $i < 13; $i++) {
            $this->contract('open');
        }

        $contracts = $this->get('/contracts')->viewData('contracts');

        $this->assertSame(12, $contracts->count());
        $this->assertTrue($contracts->hasMorePages());
    }

    public function test_filters_narrow_the_results(): void
    {
        $open = $this->contract('open', 'تجارة', 'عقد مفتوح');
        $upcoming = $this->contract('upcoming', 'عقار', 'عقد قادم');

        $this->get('/contracts?status=open')
            ->assertSee('عقد مفتوح')
            ->assertDontSee('عقد قادم');

        $this->get('/contracts?activity_type='.urlencode('عقار'))
            ->assertSee('عقد قادم')
            ->assertDontSee('عقد مفتوح');
    }

    public function test_latest_news_shows_only_published(): void
    {
        NewsUpdate::create(['title' => 'خبر منشور', 'body' => 'محتوى', 'is_published' => true, 'published_at' => now()]);
        NewsUpdate::create(['title' => 'مسودة مخفية', 'body' => 'محتوى', 'is_published' => false]);

        $this->get('/')
            ->assertSee('خبر منشور')
            ->assertDontSee('مسودة مخفية');
    }

    public function test_contracts_page_does_not_trigger_n_plus_one(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->contract('open');
        }

        DB::enableQueryLog();
        $this->get('/contracts')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // A handful of queries (pagination + filters + session) — not one per card.
        $this->assertLessThan(10, $queryCount);
    }

    public function test_featured_contracts_do_not_exceed_six(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->contract('open');
        }

        $featured = $this->get('/')->viewData('featured');

        $this->assertLessThanOrEqual(6, $featured->count());
    }
}
