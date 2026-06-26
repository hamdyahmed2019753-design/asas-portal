<?php

namespace App\Filament\Resources\InvestorResource\Widgets;

use App\Enums\KycStatus;
use App\Filament\Widgets\Base\BaseKpiWidget;
use App\Models\User;

/**
 * Stats header for InvestorResource. Reuses the design-system KPI cards via
 * BaseKpiWidget. Lives under the resource namespace so it is NOT auto-discovered
 * as a dashboard widget.
 */
class InvestorStats extends BaseKpiWidget
{
    protected int $gridCols = 4;

    protected function getCards(): array
    {
        return [
            [
                'icon' => 'heroicon-o-user-group',
                'label' => 'عدد المستثمرين',
                'value' => User::role('investor')->count(),
                'color' => 'info',
            ],
            [
                'icon' => 'heroicon-o-users',
                'label' => 'عدد الأعضاء',
                // Members who are not yet active investors (member role, no investor role).
                'value' => User::role('member')
                    ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'investor'))
                    ->count(),
                'color' => 'primary',
            ],
            [
                'icon' => 'heroicon-o-shield-check',
                'label' => 'KYC موثّق',
                'value' => User::where('kyc_status', KycStatus::Verified->value)->count(),
                'color' => 'success',
            ],
            [
                'icon' => 'heroicon-o-clock',
                'label' => 'KYC قيد المراجعة',
                'value' => User::where('kyc_status', KycStatus::Pending->value)->count(),
                'color' => 'warning',
            ],
        ];
    }
}
