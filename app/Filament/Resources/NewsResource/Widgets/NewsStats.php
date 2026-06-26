<?php

namespace App\Filament\Resources\NewsResource\Widgets;

use App\Filament\Widgets\Base\BaseKpiWidget;
use App\Models\NewsUpdate;

/**
 * Stats header for NewsResource. Reuses the design-system KPI cards via
 * BaseKpiWidget. Lives under the resource namespace so it is NOT auto-discovered
 * as a dashboard widget.
 */
class NewsStats extends BaseKpiWidget
{
    protected int $gridCols = 4;

    protected function getCards(): array
    {
        return [
            [
                'icon' => 'heroicon-o-check-circle',
                'label' => 'الأخبار المنشورة',
                'value' => NewsUpdate::where('is_published', true)->count(),
                'color' => 'success',
            ],
            [
                'icon' => 'heroicon-o-pencil-square',
                'label' => 'المسودات',
                'value' => NewsUpdate::where('is_published', false)->count(),
                'color' => 'gray',
            ],
            [
                'icon' => 'heroicon-o-calendar',
                'label' => 'أخبار هذا الشهر',
                'value' => NewsUpdate::where('created_at', '>=', now()->startOfMonth())->count(),
                'color' => 'info',
            ],
            [
                'icon' => 'heroicon-o-newspaper',
                'label' => 'إجمالي الأخبار',
                'value' => NewsUpdate::count(),
                'color' => 'primary',
            ],
        ];
    }
}
