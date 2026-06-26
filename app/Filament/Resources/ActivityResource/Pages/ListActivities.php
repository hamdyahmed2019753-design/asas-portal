<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ActivityResource\Widgets\ActivityStats;
use App\Filament\Resources\ActivityResource\Widgets\RecentActivityTimeline;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    // Read-only: no create action.
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
            ActivityStats::class,
            RecentActivityTimeline::class,
        ];
    }
}
