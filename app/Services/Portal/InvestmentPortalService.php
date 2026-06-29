<?php

namespace App\Services\Portal;

use App\Enums\InvestmentStatus;
use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Models\Contract;
use App\Models\ContractInterest;
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

        $invested = (float) $investment->amount;

        $profitPaid = (float) $payouts
            ->where('type', PayoutType::Profit)
            ->where('status', PayoutStatus::Paid)
            ->sum('amount');

        // Expected total profit = every profit payout's amount (manual/scheduled
        // amounts not yet set count as 0). Remaining = expected − received.
        $profitExpected = (float) $payouts->where('type', PayoutType::Profit)->sum('amount');
        $profitRemaining = max(0.0, $profitExpected - $profitPaid);

        $paidProfitPayouts = $payouts
            ->where('status', PayoutStatus::Paid)
            ->sortBy('paid_at')
            ->values();

        // The investor's earliest interest in this contract (timeline anchor).
        $interestAt = ContractInterest::query()
            ->where('user_id', $investment->user_id)
            ->where('contract_id', $investment->contract_id)
            ->oldest()
            ->value('created_at');

        return [
            'investment' => $investment,
            'payouts' => $payouts,
            'hasPayouts' => $payouts->isNotEmpty(),
            'investedAmount' => $invested,
            'profitPaid' => $profitPaid,
            'expectedReturn' => $investment->contract?->expected_return,
            'profit' => [
                'received' => $profitPaid,
                'remaining' => $profitRemaining,
                'expected' => $profitExpected,
                'value' => $invested + $profitPaid, // capital + realized profit
            ],
            'summary' => [
                'total' => $payouts->count(),
                'paid' => $payouts->where('status', PayoutStatus::Paid)->count(),
                'due' => $payouts->where('status', PayoutStatus::Due)->count(),
                'upcoming' => $payouts->where('status', PayoutStatus::Scheduled)->count(),
            ],
            'support' => [
                'email' => setting('general.support_email'),
                'phone' => setting('general.support_phone'),
            ],
            'timeline' => $this->buildTimeline($investment, $paidProfitPayouts, $interestAt),
        ];
    }

    /**
     * Chronological lifecycle: interest → submission → approval → payouts → end.
     *
     * @param  Collection<int, Payout>  $paidPayouts
     * @return array<int, array<string, string>>
     */
    private function buildTimeline(Investment $investment, $paidPayouts, $interestAt = null): array
    {
        $events = [];

        if ($interestAt !== null) {
            $events[] = ['title' => 'تم إرسال الاهتمام', 'date' => $interestAt->format('Y-m-d'), 'color' => 'info'];
        }

        $events[] = [
            'title' => 'تم تقديم المشاركة',
            'date' => $investment->created_at?->format('Y-m-d'),
            'color' => 'info',
        ];

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

        if ($investment->end_date) {
            $ended = $investment->end_date->isPast();
            $events[] = [
                'title' => $ended ? 'انتهى العقد' : 'نهاية العقد المتوقعة',
                'date' => $investment->end_date->format('Y-m-d'),
                'color' => $ended ? 'success' : 'gray',
            ];
        }

        return $events;
    }
}
