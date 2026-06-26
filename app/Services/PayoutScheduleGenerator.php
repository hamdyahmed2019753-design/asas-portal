<?php

namespace App\Services;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Models\Investment;
use App\Models\Payout;
use Illuminate\Support\Collection;

/**
 * Generates the payout schedule for an approved investment.
 *
 * Two kinds of rows are produced:
 *  - Profit payouts: `payouts_count` rows, due dates spread evenly across the
 *    contract duration. Amount is left NULL — entered manually by admin.
 *  - One Capital payout: due at the end of the contract, amount = investment amount.
 *
 * The investment MUST already have its `start_date` set (done by ApproveInvestment).
 */
class PayoutScheduleGenerator
{
    /**
     * @return Collection<int, Payout>
     */
    public function generate(Investment $investment): Collection
    {
        $contract = $investment->contract;
        $start = $investment->start_date->copy();
        $duration = (int) $contract->duration_months;
        $count = (int) $contract->payouts_count;

        $payouts = collect();

        // Evenly-spaced profit payouts.
        for ($i = 1; $i <= $count; $i++) {
            $monthsOffset = (int) round($duration * $i / $count);

            $payouts->push($investment->payouts()->create([
                'type' => PayoutType::Profit,
                'sequence' => $i,
                'due_date' => $start->copy()->addMonths($monthsOffset),
                'amount' => null, // manual — entered by admin before payment
                'status' => PayoutStatus::Scheduled,
            ]));
        }

        // Single capital-return payout at the end of the contract.
        $payouts->push($investment->payouts()->create([
            'type' => PayoutType::Capital,
            'sequence' => null,
            'due_date' => $start->copy()->addMonths($duration),
            'amount' => $investment->amount,
            'status' => PayoutStatus::Scheduled,
        ]));

        return $payouts;
    }
}
