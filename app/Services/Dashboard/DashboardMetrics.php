<?php

namespace App\Services\Dashboard;

use App\Enums\ContractInterestStatus;
use App\Enums\ContractStatus;
use App\Enums\InvestmentStatus;
use App\Enums\PayoutStatus;
use App\Models\Contract;
use App\Models\ContractInterest;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Single source of dashboard aggregations. Widgets stay thin and never run
 * complex queries themselves — they call these methods.
 */
class DashboardMetrics
{
    public function totalInvestors(): int
    {
        return User::role('investor')->count();
    }

    public function totalInvestments(): float
    {
        return (float) Investment::approved()->sum('amount');
    }

    public function pendingInvestments(): int
    {
        return Investment::where('status', InvestmentStatus::Pending->value)->count();
    }

    public function duePayouts(): int
    {
        return Payout::where('status', PayoutStatus::Due->value)->count();
    }

    public function pendingContractInterests(): int
    {
        return ContractInterest::where('status', ContractInterestStatus::Pending->value)->count();
    }

    public function openContracts(): int
    {
        return Contract::where('status', ContractStatus::Open->value)->count();
    }

    public function runningContracts(): int
    {
        return Contract::where('status', ContractStatus::Running->value)->count();
    }

    /**
     * Cumulative approved investment amount at the end of each of the last N months.
     *
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    public function investmentGrowth(int $months = 6): array
    {
        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $end = now()->subMonths($i)->endOfMonth();
            $labels[] = $end->translatedFormat('M');
            $data[] = round((float) Investment::approved()->where('created_at', '<=', $end)->sum('amount'), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Cumulative number of investors at the end of each of the last N months.
     *
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function investorsGrowth(int $months = 6): array
    {
        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $end = now()->subMonths($i)->endOfMonth();
            $labels[] = $end->translatedFormat('M');
            $data[] = User::role('investor')->where('created_at', '<=', $end)->count();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Payout counts by status, in fixed order: scheduled, due, paid.
     *
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function payoutStatusDistribution(): array
    {
        $order = [PayoutStatus::Scheduled, PayoutStatus::Due, PayoutStatus::Paid];

        return [
            'labels' => array_map(fn (PayoutStatus $s) => $s->label(), $order),
            'data' => array_map(fn (PayoutStatus $s) => Payout::where('status', $s->value)->count(), $order),
        ];
    }

    /**
     * Contract counts by status, in fixed order: upcoming, open, running, closed, finished.
     *
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function contractStatusDistribution(): array
    {
        $order = [
            ContractStatus::Upcoming,
            ContractStatus::Open,
            ContractStatus::Running,
            ContractStatus::Closed,
            ContractStatus::Finished,
        ];

        return [
            'labels' => array_map(fn (ContractStatus $s) => $s->label(), $order),
            'data' => array_map(fn (ContractStatus $s) => Contract::where('status', $s->value)->count(), $order),
        ];
    }

    /**
     * @return Collection<int, Investment>
     */
    public function latestInvestments(int $limit = 5): Collection
    {
        return Investment::with(['user', 'contract'])->latest()->limit($limit)->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function latestInvestors(int $limit = 5): Collection
    {
        return User::role('investor')->latest()->limit($limit)->get();
    }

    /**
     * @return Collection<int, Activity>
     */
    public function latestActivity(int $limit = 10): Collection
    {
        return Activity::with('causer')->latest()->limit($limit)->get();
    }
}
