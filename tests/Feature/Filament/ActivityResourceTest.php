<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\ActivityResource\Widgets\ActivityStats;
use App\Filament\Resources\ActivityResource\Widgets\RecentActivityTimeline;
use App\Models\Contract;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityResourceTest extends TestCase
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

    private function makeActivity(
        string $event = 'created',
        string $subjectType = Contract::class,
        array $props = ['attributes' => ['title' => 'عقد النمو']],
        ?Carbon $when = null
    ): Activity {
        $activity = new Activity;
        $activity->forceFill([
            'log_name' => 'default',
            'description' => $event,
            'subject_type' => $subjectType,
            'subject_id' => 1,
            'causer_type' => User::class,
            'causer_id' => $this->admin->id,
            'event' => $event,
            'properties' => $props,
            'created_at' => $when ?? now(),
            'updated_at' => $when ?? now(),
        ])->save();

        return $activity->refresh();
    }

    public function test_it_renders_the_list_page(): void
    {
        $this->makeActivity();

        Livewire::test(ListActivities::class)->assertSuccessful();
    }

    public function test_it_can_filter_by_event(): void
    {
        $created = $this->makeActivity('created');
        $updated = $this->makeActivity('updated');

        Livewire::test(ListActivities::class)
            ->filterTable('event', 'created')
            ->assertCanSeeTableRecords([$created])
            ->assertCanNotSeeTableRecords([$updated]);
    }

    public function test_it_can_filter_by_subject(): void
    {
        $contract = $this->makeActivity('created', Contract::class);
        $user = $this->makeActivity('created', User::class);

        Livewire::test(ListActivities::class)
            ->filterTable('subject_type', Contract::class)
            ->assertCanSeeTableRecords([$contract])
            ->assertCanNotSeeTableRecords([$user]);
    }

    public function test_it_can_filter_by_date(): void
    {
        $recent = $this->makeActivity('created', Contract::class, when: now());
        $old = $this->makeActivity('created', Contract::class, when: now()->subMonths(2));

        Livewire::test(ListActivities::class)
            ->filterTable('created_at', ['from' => now()->toDateString()])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_stats_header_renders(): void
    {
        $this->makeActivity('created');
        $this->makeActivity('updated');

        Livewire::test(ActivityStats::class)
            ->assertSuccessful()
            ->assertSee('إجمالي الأحداث')
            ->assertSee('أحداث اليوم')
            ->assertSee('إنشاءات')
            ->assertSee('تعديلات');
    }

    public function test_timeline_widget_renders(): void
    {
        $this->makeActivity('created');

        Livewire::test(RecentActivityTimeline::class)->assertSuccessful();
    }

    public function test_details_action_can_be_mounted(): void
    {
        $activity = $this->makeActivity();

        Livewire::test(ListActivities::class)
            ->assertTableActionVisible('details', $activity)
            ->mountTableAction('details', $activity)
            ->assertSuccessful();
    }

    public function test_properties_are_formatted_as_pretty_unescaped_json(): void
    {
        $activity = $this->makeActivity('created', Contract::class, props: ['attributes' => ['title' => 'عقد النمو']]);

        $json = ActivityResource::formatProperties($activity);

        $this->assertStringContainsString('عقد النمو', $json); // unescaped unicode
        $this->assertStringContainsString("\n", $json);        // pretty printed
    }

    public function test_subject_label_maps_known_and_unknown_classes(): void
    {
        $this->assertSame('عقد', ActivityResource::subjectLabel(Contract::class));
        $this->assertSame('مستخدم', ActivityResource::subjectLabel(User::class));
        $this->assertSame('غير معروف', ActivityResource::subjectLabel('App\\Models\\Other'));
    }
}
