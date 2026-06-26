<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Base\BaseChartWidget;
use App\Services\Dashboard\DashboardMetrics;

class PayoutStatusChart extends BaseChartWidget
{
    protected static ?string $chartId = 'payoutStatusChart';

    protected static ?string $heading = 'حالة التوزيعات';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $dist = app(DashboardMetrics::class)->payoutStatusDistribution();

        return array_replace_recursive($this->commonOptions(), [
            'chart' => ['type' => 'donut', 'height' => 260],
            'series' => $dist['data'],
            'labels' => $dist['labels'],
            // scheduled -> gray, due -> warning, paid -> success
            'colors' => [self::COLOR_GRAY, self::COLOR_WARNING, self::COLOR_SUCCESS],
        ]);
    }
}
