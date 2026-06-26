<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Base\BaseActivityWidget;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Services\Dashboard\DashboardMetrics;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityTimeline extends BaseActivityWidget
{
    protected static ?int $sort = 8;

    protected string $heading = 'سجل النشاط';

    protected string $emptyTitle = 'لا يوجد نشاط بعد';

    protected string $emptyDescription = 'سيظهر هنا أحدث نشاط على العقود والمشاركات والتوزيعات.';

    protected int $limit = 10;

    /**
     * @var array<class-string, string>
     */
    protected const SUBJECTS = [
        Contract::class => 'عقد',
        Investment::class => 'مشاركة',
        Payout::class => 'توزيعة',
    ];

    /**
     * @var array<string, string>
     */
    protected const EVENTS = [
        'created' => 'إنشاء',
        'updated' => 'تحديث',
        'deleted' => 'حذف',
    ];

    protected function getItems(): Collection
    {
        return app(DashboardMetrics::class)->latestActivity($this->limit)->map(function (Activity $activity): array {
            $event = $activity->event ?? 'updated';
            $subject = self::SUBJECTS[$activity->subject_type] ?? 'عنصر';
            $verb = self::EVENTS[$event] ?? $event;

            return [
                'event' => $event,
                'title' => trim($verb.' '.$subject.($activity->causer?->name ? ' · بواسطة '.$activity->causer->name : '')),
                'time' => $activity->created_at?->diffForHumans(),
            ];
        });
    }
}
