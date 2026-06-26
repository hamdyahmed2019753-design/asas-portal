<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Base\BaseChartWidget;
use App\Services\Dashboard\DashboardMetrics;

class InvestmentGrowthChart extends BaseChartWidget
{
    protected static ?string $chartId = 'investmentGrowthChart';

    protected static ?string $heading = 'نمو الاستثمارات';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $growth = app(DashboardMetrics::class)->investmentGrowth();

        return array_replace_recursive($this->commonOptions(), [
            'chart' => ['type' => 'area', 'height' => 260],
            'series' => [[
                'name' => 'إجمالي الاستثمارات',
                'data' => $growth['data'],
            ]],
            'xaxis' => ['categories' => $growth['labels']],
            'colors' => [self::COLOR_TEAL],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => ['type' => 'solid', 'opacity' => 0.15],
        ]);
    }
}
