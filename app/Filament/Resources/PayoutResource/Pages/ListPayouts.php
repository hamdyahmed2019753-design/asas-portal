<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use App\Filament\Resources\PayoutResource\Widgets\PayoutStats;
use Filament\Resources\Pages\ListRecords;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    // No create action — payouts are generated automatically on approval.
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            PayoutStats::class,
        ];
    }
}
