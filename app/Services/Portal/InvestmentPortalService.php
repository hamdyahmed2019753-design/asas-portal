<?php

namespace App\Services\Portal;

use App\Enums\InvestmentStatus;
use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * All queries for the investor "My Investments" pages. Everything is scoped to
 * the given user via their own relationship — never a global filtered query.
 */
class InvestmentPortalService
{
    /**
     * Paginated, filtered list of the user's own investments.
     *
     * @return array<string, mixed>
     */
    public function list(User $user, Request $request): array
    {
        $query = $user->investments()
            ->with('contract')
            ->withCount('payouts');

        $status = $request->query('status');
        if (is_string($status) && in_array($status, array_column(InvestmentStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        $contractId = $request->query('contract');
        if (is_numeric($contractId)) {
            $query->where('contract_id', (int) $contractId);
        }

        if ($request->boolean('active')) {
            $query->approved();
        }

        return [
            'investments' => $query->latest()->paginate(10)->withQueryString(),
            'contracts' => Contract::query()
                ->whereIn('id', $user->investments()->select('contract_id'))
                ->orderBy('title')
                ->get(['id', 'title']),
            'statusOptions' => collect(InvestmentStatus::cases())
                ->mapWithKeys(fn (InvestmentStatus $s) => [$s->value => $s->label()])
                ->all(),
        ];
    }

    /**
     * The user's own (latest) investment in a given contract, if any — scoped
     * through their relationship so it can never leak another user's data.
     */
    public function forContract(User $user, Contract $contract): ?Investment
    {
        return $user->investments()
            ->where('contract_id', $contract->id)
            ->latest()
            ->first();
    }

    /**
     * Detail payload for a single (already authorised) investment.
     *
     * @return array<string, mixed>
     */
    public function details(Investment $investment): array
    {
        $investment->loadMissing(['contract', 'payouts' => fn ($q) => $q->orderBy('due_date')]);
        $payouts = $investment->payouts;

        $profitPaid = (float) $payouts
            ->where('type', PayoutType::Profit)
            ->where('status', PayoutStatus::Paid)
            ->sum('amount');

        $paidProfitPayouts = $payouts
            ->where('status', PayoutStatus::Paid)
            ->sortBy('paid_at')
            ->values();

        return [
            'investment' => $investment,
            'payouts' => $payouts,
            'hasPayouts' => $payouts->isNotEmpty(),
            'investedAmount' => (float) $investment->amount,
            'profitPaid' => $profitPaid,
            'expectedReturn' => $investment->contract?->expected_return,
            'summary' => [
                'total' => $payouts->count(),
                'paid' => $payouts->where('status', PayoutStatus::Paid)->count(),
                'due' => $payouts->where('status', PayoutStatus::Due)->count(),
                'upcoming' => $payouts->where('status', PayoutStatus::Scheduled)->count(),
            ],
            'timeline' => $this->buildTimeline($investment, $paidProfitPayouts),
        ];
    }

    /**
     * @param  Collection<int, Payout>  $paidPayouts
     * @return array<int, array<string, string>>
     */
    private function buildTimeline(Investment $investment, $paidPayouts): array
    {
        $events = [[
            'title' => 'تم تقديم المشاركة',
            'date' => $investment->created_at?->format('Y-m-d'),
            'color' => 'info',
        ]];

        if ($investment->approved_at) {
            $events[] = ['title' => 'تم اعتماد المشاركة', 'date' => $investment->approved_at->format('Y-m-d'), 'color' => 'success'];
        }
        if ($investment->rejected_at) {
            $events[] = ['title' => 'تم رفض المشاركة', 'date' => $investment->rejected_at->format('Y-m-d'), 'color' => 'danger'];
        }

        if ($paidPayouts->isNotEmpty()) {
            $events[] = ['title' => 'أول توزيعة مدفوعة', 'date' => $paidPayouts->first()->paid_at?->format('Y-m-d'), 'color' => 'primary'];

            if ($paidPayouts->count() > 1) {
                $events[] = ['title' => 'آخر توزيعة مدفوعة', 'date' => $paidPayouts->last()->paid_at?->format('Y-m-d'), 'color' => 'success'];
            }
        }

        return $events;
    }
}
