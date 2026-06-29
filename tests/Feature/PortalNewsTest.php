<?php

namespace Tests\Feature;

use App\Models\NewsUpdate;
use App\Models\User;
use App\Services\Portal\NewsPortalService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalNewsTest extends TestCase
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

    private function news(string $title, bool $published, ?string $at = '2026-01-01'): NewsUpdate
    {
        return NewsUpdate::create([
            'title' => $title,
            'body' => 'محتوى الخبر التفصيلي هنا.',
            'is_published' => $published,
            'published_at' => $at,
        ]);
    }

    public function test_only_published_news_is_shown(): void
    {
        $this->news('منشور', true, '2026-01-01');
        $this->news('مسودة', false, '2026-01-01');
        $this->news('مجدول مستقبلًا', true, now()->addWeek()->toDateString());

        $news = app(NewsPortalService::class)->data()['news'];

        $this->assertCount(1, $news);
        $this->assertSame('منشور', $news->first()->title);
    }

    public function test_news_is_paginated_ten_per_page(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->news("خبر {$i}", true, '2026-01-0'.(($i % 9) + 1));
        }

        $news = app(NewsPortalService::class)->data()['news'];

        $this->assertSame(10, $news->perPage());
        $this->assertCount(10, $news);
        $this->assertSame(12, $news->total());
    }

    public function test_empty_state_when_no_published_news(): void
    {
        $this->news('مسودة', false);

        $this->actingAs($this->member())
            ->get('/portal/news')
            ->assertOk()
            ->assertSee('لا توجد أخبار');
    }

    public function test_news_page_does_not_trigger_n_plus_one(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->news("خبر {$i}", true, '2026-01-0'.(($i % 9) + 1));
        }

        DB::enableQueryLog();
        $this->actingAs($this->member())->get('/portal/news')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $count);
    }
}
