<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Filament\Widgets\ActivityTimeline;

/**
 * Reuses the read-only ActivityTimeline widget as the header of ActivityResource,
 * showing the last 15 activities. Lives under the resource namespace so it is not
 * auto-discovered as a dashboard widget.
 */
class RecentActivityTimeline extends ActivityTimeline
{
    protected static ?int $sort = null;

    protected int $limit = 15;

    protected string $heading = 'آخر النشاطات';
}
