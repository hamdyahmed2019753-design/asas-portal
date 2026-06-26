<?php

namespace App\Filament\Resources\PayoutResource\Widgets;

use App\Enums\PayoutStatus;
use App\Filament\Widgets\Base\BaseKpiWidget;
use App\Models\Payout;

/**
 * Stats header for PayoutResource. Reuses the design-system KPI cards via
 * BaseKpiWidget. Lives under the resource namespace so it is NOT auto-discovered
 * as a dashboard widget.
 */
class PayoutStats extends BaseKpiWidget
{
    protected int $gridCols = 4;

    protected function getCards(): array
    {
        return [
            [
                'icon' => 'heroicon-o-calendar-days',
                'label' => 'توزيعات مجدولة',
                'value' => Payout::where('status', PayoutStatus::Scheduled->value)->count(),
                'color' => 'gray',
            ],
            [
                'icon' => 'heroicon-o-exclamation-circle',
                'label' => 'توزيعات مستحقة',
                'value' => Payout::where('status', PayoutStatus::Due->value)->count(),
                'color' => 'warning',
            ],
            [
                'icon' => 'heroicon-o-check-badge',
                'label' => 'توزيعات مدفوعة',
                'value' => Payout::where('status', PayoutStatus::Paid->value)->count(),
                'color' => 'success',
            ],
            [
                'icon' => 'heroicon-o-banknotes',
                'label' => 'إجمالي الأرباح المدفوعة',
                'value' => money(Payout::query()->profit()->paid()->sum('amount')),
                'color' => 'primary',
            ],
        ];
    }
}
