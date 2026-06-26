<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Base\BaseChartWidget;
use App\Services\Dashboard\DashboardMetrics;

class InvestorsGrowthChart extends BaseChartWidget
{
    protected static ?string $chartId = 'investorsGrowthChart';

    protected static ?string $heading = 'نمو المستثمرين';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $growth = app(DashboardMetrics::class)->investorsGrowth();

        return array_replace_recursive($this->commonOptions(), [
            'chart' => ['type' => 'area', 'height' => 260],
            'series' => [[
                'name' => 'عدد المستثمرين',
                'data' => $growth['data'],
            ]],
            'xaxis' => ['categories' => $growth['labels']],
            'colors' => [self::COLOR_BLUE],
            'stroke' => ['curve' => 'smooth', 'width' => 2],
            'fill' => ['type' => 'solid', 'opacity' => 0.15],
        ]);
    }
}
