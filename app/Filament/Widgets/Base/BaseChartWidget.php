<?php

namespace App\Filament\Widgets\Base;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

/**
 * Base for ApexCharts widgets. Centralises the token-bound chart palette and the
 * common RTL/Tajawal options so concrete charts never use random colours.
 * Abstract — never registered/discovered directly.
 *
 * ApexCharts renders in JS and cannot read CSS variables, so the design tokens
 * are mirrored here as hex constants (kept in sync with docs/DESIGN_SYSTEM.md).
 */
abstract class BaseChartWidget extends ApexChartWidget
{
    protected const COLOR_TEAL = '#15A878';

    protected const COLOR_BLUE = '#2D7FC4';

    protected const COLOR_SUCCESS = '#1F9D57';

    protected const COLOR_WARNING = '#E2A00F';

    protected const COLOR_DANGER = '#E04B43';

    protected const COLOR_GRAY = '#9BA3A3';

    protected const GRID_COLOR = '#E1E5E5';

    protected static bool $isLazy = false;

    /**
     * Common chart options shared by every Asas chart (RTL, font, no toolbar).
     *
     * @return array<string, mixed>
     */
    protected function commonOptions(): array
    {
        return [
            'chart' => [
                'fontFamily' => 'Tajawal, sans-serif',
                'toolbar' => ['show' => false],
                'zoom' => ['enabled' => false],
            ],
            'grid' => [
                'borderColor' => self::GRID_COLOR,
                'strokeDashArray' => 4,
            ],
            'tooltip' => ['rtl' => true],
            'legend' => [
                'fontFamily' => 'Tajawal, sans-serif',
                'position' => 'bottom',
            ],
            'dataLabels' => ['enabled' => false],
        ];
    }

    /**
     * Fixed palette for status-style (categorical) charts.
     *
     * @return array<int, string>
     */
    protected function statusPalette(): array
    {
        return [
            self::COLOR_SUCCESS,
            self::COLOR_WARNING,
            self::COLOR_DANGER,
            self::COLOR_GRAY,
        ];
    }
}
