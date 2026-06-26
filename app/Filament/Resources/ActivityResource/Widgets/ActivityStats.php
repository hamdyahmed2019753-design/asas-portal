<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Filament\Widgets\Base\BaseKpiWidget;
use Spatie\Activitylog\Models\Activity;

/**
 * Stats header for ActivityResource. Reuses the design-system KPI cards via
 * BaseKpiWidget. Lives under the resource namespace so it is NOT auto-discovered
 * as a dashboard widget.
 */
class ActivityStats extends BaseKpiWidget
{
    protected int $gridCols = 4;

    protected function getCards(): array
    {
        return [
            [
                'icon' => 'heroicon-o-bolt',
                'label' => 'إجمالي الأحداث',
                'value' => Activity::count(),
                'color' => 'primary',
            ],
            [
                'icon' => 'heroicon-o-calendar',
                'label' => 'أحداث اليوم',
                'value' => Activity::whereDate('created_at', today())->count(),
                'color' => 'info',
            ],
            [
                'icon' => 'heroicon-o-plus-circle',
                'label' => 'إنشاءات',
                'value' => Activity::where('event', 'created')->count(),
                'color' => 'success',
            ],
            [
                'icon' => 'heroicon-o-pencil-square',
                'label' => 'تعديلات',
                'value' => Activity::where('event', 'updated')->count(),
                'color' => 'warning',
            ],
        ];
    }
}
