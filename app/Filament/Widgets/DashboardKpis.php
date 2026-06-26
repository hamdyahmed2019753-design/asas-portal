<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Base\BaseKpiWidget;
use App\Services\Dashboard\DashboardMetrics;

class DashboardKpis extends BaseKpiWidget
{
    protected static ?int $sort = 1;

    protected function getCards(): array
    {
        $metrics = app(DashboardMetrics::class);

        return [
            [
                'icon' => 'heroicon-o-users',
                'label' => 'إجمالي المستثمرين',
                'value' => $metrics->totalInvestors(),
                'color' => 'info',
            ],
            [
                'icon' => 'heroicon-o-banknotes',
                'label' => 'إجمالي الاستثمارات',
                'value' => money($metrics->totalInvestments()),
                'color' => 'primary',
            ],
            [
                'icon' => 'heroicon-o-clock',
                'label' => 'المشاركات المعلّقة',
                'value' => $metrics->pendingInvestments(),
                'color' => 'warning',
            ],
            [
                'icon' => 'heroicon-o-exclamation-circle',
                'label' => 'التوزيعات المستحقة',
                'value' => $metrics->duePayouts(),
                'color' => 'warning',
            ],
            [
                'icon' => 'heroicon-o-hand-raised',
                'label' => 'طلبات الاهتمام المعلّقة',
                'value' => $metrics->pendingContractInterests(),
                'color' => 'info',
            ],
            [
                'icon' => 'heroicon-o-lock-open',
                'label' => 'العقود المفتوحة',
                'value' => $metrics->openContracts(),
                'color' => 'success',
            ],
            [
                'icon' => 'heroicon-o-arrow-path',
                'label' => 'العقود الجارية',
                'value' => $metrics->runningContracts(),
                'color' => 'primary',
            ],
        ];
    }
}
