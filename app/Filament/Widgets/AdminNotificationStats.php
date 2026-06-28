<?php

namespace App\Filament\Widgets;

use App\Enums\AdminNotificationPriority;
use App\Filament\Widgets\Base\BaseKpiWidget;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Notification Center KPIs on the dashboard: total unread, today's
 * notifications, this week, and critical-priority count. Scoped to the signed-in
 * admin's own AdminNotification rows and cached briefly to avoid repeated
 * counting on every dashboard render.
 */
class AdminNotificationStats extends BaseKpiWidget
{
    protected static ?int $sort = 2;

    protected int $gridCols = 4;

    private const CACHE_TTL = 30; // seconds

    protected function getCards(): array
    {
        $adminId = (int) auth()->id();

        $metrics = Cache::remember(
            "admin.notif.stats.{$adminId}",
            now()->addSeconds(self::CACHE_TTL),
            fn () => $this->metrics($adminId),
        );

        return [
            [
                'icon' => 'heroicon-o-envelope',
                'label' => 'غير المقروءة',
                'value' => $metrics['unread'],
                'color' => 'primary',
            ],
            [
                'icon' => 'heroicon-o-calendar-days',
                'label' => 'إشعارات اليوم',
                'value' => $metrics['today'],
                'color' => 'info',
            ],
            [
                'icon' => 'heroicon-o-clock',
                'label' => 'هذا الأسبوع',
                'value' => $metrics['week'],
                'color' => 'success',
            ],
            [
                'icon' => 'heroicon-o-exclamation-triangle',
                'label' => 'إشعارات حرجة',
                'value' => $metrics['critical'],
                'color' => 'danger',
            ],
        ];
    }

    /**
     * @return array{unread: int, today: int, week: int, critical: int}
     */
    private function metrics(int $adminId): array
    {
        $base = fn () => \Illuminate\Notifications\DatabaseNotification::query()
            ->where('notifiable_id', $adminId)
            ->where('type', AdminNotification::class);

        return [
            'unread' => $base()->whereNull('read_at')->count(),
            'today' => $base()->whereDate('created_at', Carbon::today())->count(),
            'week' => $base()->where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'critical' => $base()->where('data->priority', AdminNotificationPriority::Critical->value)->count(),
        ];
    }
}
