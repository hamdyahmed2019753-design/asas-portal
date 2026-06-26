<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Base\BaseChartWidget;
use App\Services\Dashboard\DashboardMetrics;

class ContractStatusChart extends BaseChartWidget
{
    protected static ?string $chartId = 'contractStatusChart';

    protected static ?string $heading = 'حالة العقود';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected static ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $dist = app(DashboardMetrics::class)->contractStatusDistribution();

        return array_replace_recursive($this->commonOptions(), [
            'chart' => ['type' => 'donut', 'height' => 260],
            'series' => $dist['data'],
            'labels' => $dist['labels'],
            // upcoming -> info, open -> success, running -> primary, closed -> warning, finished -> gray
            'colors' => [self::COLOR_BLUE, self::COLOR_SUCCESS, self::COLOR_TEAL, self::COLOR_WARNING, self::COLOR_GRAY],
        ]);
    }
}
