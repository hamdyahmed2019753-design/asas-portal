<?php

namespace App\Services\Portal;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the portfolio page (/portal/portfolio) needs. Every figure is
 * derived from the user's OWN relationships (investments / payouts) — never a
 * global query and never a manual user_id filter. Aggregations are eager-loaded
 * so there is no N+1, and the performance chart is a single grouped query.
 */
class PortfolioService
{
    /**
     * @return array<string, mixed>
     */
    public function data(User $user): array
    {
        // One eager-loaded pass: contract + per-investment realized/expected profit.
        $investments = $user->investments()
            ->approved()
            ->with('contract:id,title,expected_return')
            ->withSum(['payouts as paid_profit' => fn ($q) => $q
                ->where('type', PayoutType::Profit->value)
                ->where('status', PayoutStatus::Paid->value)], 'amount')
            ->withSum(['payouts as expected_profit' => fn ($q) => $q
                ->where('type', PayoutType::Profit->value)], 'amount')
            ->get();

        $totalCapital = (float) $investments->sum(fn ($i) => (float) $i->amount);
        $realizedProfit = (float) $investments->sum(fn ($i) => (float) $i->paid_profit);
        $expectedProfit = (float) $investments->sum(fn ($i) => (float) $i->expected_profit);

        return [
            'hasInvestments' => $investments->isNotEmpty(),
            'kpis' => [
                'totalCapital' => $totalCapital,
                'realizedProfit' => $realizedProfit,
                'expectedProfit' => $expectedProfit,
                'activeCount' => $investments->count(),
                'averageReturn' => $this->averageReturn($investments, $totalCapital),
                'portfolioValue' => $totalCapital + $realizedProfit,
            ],
            'allocation' => $this->allocation($investments, $totalCapital),
            'performance' => $this->performance($user),
            'investments' => $investments,
        ];
    }

    /**
     * Capital-weighted average expected return across active investments.
     *
     * @param  Collection<int, Investment>  $investments
     */
    private function averageReturn(Collection $investments, float $totalCapital): float
    {
        if ($totalCapital <= 0) {
            return 0.0;
        }

        $weighted = $investments->sum(
            fn ($i) => (float) $i->amount * (float) ($i->contract->expected_return ?? 0)
        );

        return round($weighted / $totalCapital, 2);
    }

    /**
     * Capital allocation grouped by contract (label, amount, percentage).
     * Derived from the already-loaded collection — no extra queries.
     *
     * @param  Collection<int, Investment>  $investments
     * @return array<string, mixed>
     */
    private function allocation(Collection $investments, float $totalCapital): array
    {
        $groups = $investments
            ->groupBy(fn ($i) => $i->contract->title ?? '—')
            ->map(fn (Collection $group) => (float) $group->sum(fn ($i) => (float) $i->amount));

        return [
            'labels' => $groups->keys()->all(),
            'data' => $groups->values()->all(),
            'percentages' => $groups
                ->map(fn (float $amount) => $totalCapital > 0 ? round($amount / $totalCapital * 100, 1) : 0.0)
                ->values()
                ->all(),
        ];
    }

    /**
     * Monthly realized profit — a SINGLE grouped aggregate query (no per-month
     * loop). Scoped through the user's own payouts relationship.
     *
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    private function performance(User $user): array
    {
        $monthExpr = $user->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', payouts.paid_at)"
            : "DATE_FORMAT(payouts.paid_at, '%Y-%m')";

        $rows = $user->payouts()
            ->where('payouts.type', PayoutType::Profit->value)
            ->where('payouts.status', PayoutStatus::Paid->value)
            ->whereNotNull('payouts.paid_at')
            ->where('payouts.paid_at', '>=', now()->subMonths(11)->startOfMonth())
            ->toBase()
            ->select(DB::raw("{$monthExpr} as ym"))
            ->selectRaw('SUM(payouts.amount) as total')
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return [
            'labels' => $rows->pluck('ym')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }
}
