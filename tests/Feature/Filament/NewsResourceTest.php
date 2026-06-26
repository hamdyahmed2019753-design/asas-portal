<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\NewsResource\Pages\CreateNews;
use App\Filament\Resources\NewsResource\Pages\EditNews;
use App\Filament\Resources\NewsResource\Pages\ListNews;
use App\Filament\Resources\NewsResource\Widgets\NewsStats;
use App\Models\NewsUpdate;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewsResourceTest extends TestCase
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

    private function makeNews(bool $published = false): NewsUpdate
    {
        return NewsUpdate::create([
            'title' => 'خبر '.uniqid(),
            'body' => 'محتوى الخبر',
            'is_published' => $published,
            'published_at' => $published ? now() : null,
        ]);
    }

    public function test_it_can_create_news(): void
    {
        Livewire::test(CreateNews::class)
            ->fillForm([
                'title' => 'إطلاق البوابة',
                'body' => 'تم إطلاق النسخة الأولى.',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('news_updates', ['title' => 'إطلاق البوابة', 'is_published' => false]);
    }

    public function test_publishing_on_create_stamps_published_at(): void
    {
        Livewire::test(CreateNews::class)
            ->fillForm([
                'title' => 'خبر منشور',
                'body' => 'محتوى',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $news = NewsUpdate::where('title', 'خبر منشور')->first();
        $this->assertTrue($news->is_published);
        $this->assertNotNull($news->published_at);
    }

    public function test_it_can_edit_news(): void
    {
        $news = $this->makeNews();

        Livewire::test(EditNews::class, ['record' => $news->getRouteKey()])
            ->fillForm(['title' => 'عنوان محدّث'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('عنوان محدّث', $news->refresh()->title);
    }

    public function test_it_can_delete_news(): void
    {
        $news = $this->makeNews();

        Livewire::test(ListNews::class)
            ->callTableAction('delete', $news);

        $this->assertModelMissing($news);
    }

    public function test_publish_action_publishes_a_draft(): void
    {
        $news = $this->makeNews(published: false);

        Livewire::test(ListNews::class)
            ->assertTableActionVisible('publish', $news)
            ->assertTableActionHidden('unpublish', $news)
            ->callTableAction('publish', $news);

        $news->refresh();
        $this->assertTrue($news->is_published);
        $this->assertNotNull($news->published_at);
    }

    public function test_unpublish_action_unpublishes(): void
    {
        $news = $this->makeNews(published: true);

        Livewire::test(ListNews::class)
            ->assertTableActionVisible('unpublish', $news)
            ->assertTableActionHidden('publish', $news)
            ->callTableAction('unpublish', $news);

        $this->assertFalse($news->refresh()->is_published);
    }

    public function test_bulk_publish(): void
    {
        $a = $this->makeNews(published: false);
        $b = $this->makeNews(published: false);

        Livewire::test(ListNews::class)
            ->callTableBulkAction('publish', [$a, $b]);

        $this->assertTrue($a->refresh()->is_published);
        $this->assertTrue($b->refresh()->is_published);
    }

    public function test_bulk_unpublish(): void
    {
        $a = $this->makeNews(published: true);
        $b = $this->makeNews(published: true);

        Livewire::test(ListNews::class)
            ->callTableBulkAction('unpublish', [$a, $b]);

        $this->assertFalse($a->refresh()->is_published);
        $this->assertFalse($b->refresh()->is_published);
    }

    public function test_it_can_filter_by_publish_status(): void
    {
        $published = $this->makeNews(published: true);
        $draft = $this->makeNews(published: false);

        Livewire::test(ListNews::class)
            ->filterTable('is_published', true)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft]);
    }

    public function test_stats_widget_renders_the_four_kpis(): void
    {
        $this->makeNews(published: true);
        $this->makeNews(published: false);

        Livewire::test(NewsStats::class)
            ->assertSuccessful()
            ->assertSee('الأخبار المنشورة')
            ->assertSee('المسودات')
            ->assertSee('أخبار هذا الشهر')
            ->assertSee('إجمالي الأخبار');
    }
}
