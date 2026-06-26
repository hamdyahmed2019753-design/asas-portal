<?php

namespace App\Filament\Widgets\Base;

use Filament\Widgets\Widget;

/**
 * Base for KPI widgets. Concrete widgets implement getCards() to return an array
 * of card prop-sets; this base renders them in a stats grid using the reusable
 * <x-asas.kpi-card> component. Abstract — never registered/discovered directly.
 *
 * Each card item supports keys:
 *   icon, value, label, color, trend, trendColor, trendIcon, state
 */
abstract class BaseKpiWidget extends Widget
{
    protected static string $view = 'filament.widgets.base.kpi-widget';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * Number of columns in the KPI grid (3 or 4).
     */
    protected int $gridCols = 3;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract protected function getCards(): array;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'cards' => $this->getCards(),
            'cols' => $this->gridCols,
        ];
    }
}
