<?php

namespace App\Services\Portal;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * All queries for the investor payouts page. Scoped entirely to the user's own
 * payouts (User::payouts() goes through their investments) — never a global
 * Payout query. Columns are qualified (payouts.*) because of the join.
 */
class PayoutPortalService
{
    /**
     * @return array<string, mixed>
     */
    public function data(User $user, Request $request): array
    {
        $tab = in_array($request->query('tab'), ['paid', 'due', 'upcoming'], true)
            ? $request->query('tab')
            : 'all';

        // Single eager load for the table rows.
        $rows = $user->payouts()->with('investment.contract');

        match ($tab) {
            'paid' => $rows->where('payouts.status', PayoutStatus::Paid->value),
            'due' => $rows->where('payouts.status', PayoutStatus::Due->value),
            'upcoming' => $rows->where('payouts.status', PayoutStatus::Scheduled->value),
            default => null,
        };

        $contractId = $request->query('contract');
        if (is_numeric($contractId)) {
            $rows->whereHas('investment', fn ($q) => $q->where('contract_id', (int) $contractId));
        }

        $type = $request->query('type');
        if (in_array($type, array_column(PayoutType::cases(), 'value'), true)) {
            $rows->where('payouts.type', $type);
        }

        $year = $request->query('year');
        if (is_numeric($year)) {
            $rows->whereYear('payouts.due_date', (int) $year);
        }

        return [
            'payouts' => $rows->orderBy('payouts.due_date')->get(),
            'tab' => $tab,
            'kpis' => [
                'paid' => $user->payouts()->where('payouts.status', PayoutStatus::Paid->value)->count(),
                'due' => $user->payouts()->where('payouts.status', PayoutStatus::Due->value)->count(),
                'upcoming' => $user->payouts()->where('payouts.status', PayoutStatus::Scheduled->value)->count(),
                'profitPaid' => (float) $user->payouts()
                    ->where('payouts.type', PayoutType::Profit->value)
                    ->where('payouts.status', PayoutStatus::Paid->value)
                    ->sum('payouts.amount'),
            ],
            'contracts' => Contract::query()
                ->whereIn('id', $user->investments()->select('contract_id'))
                ->orderBy('title')
                ->get(['id', 'title']),
            'years' => $user->payouts()
                ->orderByDesc('payouts.due_date')
                ->get(['payouts.id', 'payouts.due_date'])
                ->pluck('due_date')
                ->filter()
                ->map(fn ($date) => $date->year)
                ->unique()
                ->values(),
            'typeOptions' => collect(PayoutType::cases())
                ->mapWithKeys(fn (PayoutType $t) => [$t->value => $t->label()])
                ->all(),
        ];
    }
}
