<?php

namespace App\Filament\Widgets;

use App\Enums\KycStatus;
use App\Filament\Widgets\Base\BaseActivityWidget;
use App\Models\User;
use App\Services\Dashboard\DashboardMetrics;
use Illuminate\Support\Collection;

class LatestInvestors extends BaseActivityWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 1;

    protected string $heading = 'أحدث المستثمرين';

    protected string $emptyTitle = 'لا يوجد مستثمرون بعد';

    protected string $emptyDescription = 'سيظهر هنا أحدث المستثمرين بعد اعتماد مشاركاتهم.';

    protected function getItems(): Collection
    {
        return app(DashboardMetrics::class)->latestInvestors(5)->map(fn (User $user): array => [
            'event' => $this->eventFor($user->kyc_status),
            'title' => trim(($user->name ?? 'مستثمر').' · '.$user->kyc_status->label()),
            'time' => $user->created_at?->diffForHumans(),
        ]);
    }

    /**
     * Map the KYC status to a timeline event token (in PHP, never in the view).
     */
    private function eventFor(KycStatus $status): string
    {
        return match ($status) {
            KycStatus::Verified => 'created',
            KycStatus::Pending => 'updated',
            KycStatus::Rejected => 'deleted',
        };
    }
}
