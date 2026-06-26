<?php

namespace App\Filament\Widgets;

use App\Enums\InvestmentStatus;
use App\Filament\Widgets\Base\BaseActivityWidget;
use App\Models\Investment;
use App\Services\Dashboard\DashboardMetrics;
use Illuminate\Support\Collection;

class LatestInvestments extends BaseActivityWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected string $heading = 'أحدث المشاركات';

    protected string $emptyTitle = 'لا توجد مشاركات بعد';

    protected string $emptyDescription = 'ستظهر هنا أحدث طلبات المشاركة فور تقديمها.';

    protected function getItems(): Collection
    {
        return app(DashboardMetrics::class)->latestInvestments(5)->map(fn (Investment $investment): array => [
            'event' => $this->eventFor($investment->status),
            'title' => trim(($investment->user?->name ?? 'مستخدم').' — '.money($investment->amount).' · '.($investment->contract?->title ?? '')),
            'time' => $investment->created_at?->diffForHumans(),
        ]);
    }

    /**
     * Map the investment status to a timeline event token (in PHP, never in the view).
     */
    private function eventFor(InvestmentStatus $status): string
    {
        return match ($status) {
            InvestmentStatus::Approved => 'created',
            InvestmentStatus::Pending => 'updated',
            InvestmentStatus::Rejected => 'deleted',
        };
    }
}
