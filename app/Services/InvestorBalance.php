<?php

namespace App\Services;

use App\DTOs\InvestorBalanceData;
use App\Models\Payout;
use App\Models\User;

/**
 * Computes the financial balances shown on the investor dashboard.
 *
 * All figures are derived from the user's APPROVED investments only.
 * Profit and capital are kept strictly separate (capital returns as its own
 * payout type at the end of the contract).
 */
class InvestorBalance
{
    public function for(User $user): InvestorBalanceData
    {
        $approvedInvestmentIds = $user->investments()->approved()->pluck('id');

        $totalInvested = (float) $user->investments()->approved()->sum('amount');

        $totalProfitPaid = (float) Payout::query()
            ->whereIn('investment_id', $approvedInvestmentIds)
            ->profit()
            ->paid()
            ->sum('amount');

        $totalProfitExpected = (float) Payout::query()
            ->whereIn('investment_id', $approvedInvestmentIds)
            ->profit()
            ->sum('amount');

        $capitalReturned = (float) Payout::query()
            ->whereIn('investment_id', $approvedInvestmentIds)
            ->capital()
            ->paid()
            ->sum('amount');

        $nextPayout = Payout::query()
            ->whereIn('investment_id', $approvedInvestmentIds)
            ->upcoming()
            ->orderBy('due_date')
            ->first();

        return new InvestorBalanceData(
            totalInvested: $totalInvested,
            totalProfitPaid: $totalProfitPaid,
            totalProfitExpected: $totalProfitExpected,
            capitalReturned: $capitalReturned,
            nextPayout: $nextPayout,
        );
    }
}
