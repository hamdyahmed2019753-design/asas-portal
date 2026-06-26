<?php

namespace App\Actions\Investments;

use App\Enums\InvestmentStatus;
use App\Exceptions\InvestmentAlreadyProcessedException;
use App\Models\Investment;
use App\Notifications\InvestmentRejectedNotification;
use Illuminate\Support\Facades\DB;

/**
 * Rejects a pending investment:
 *  - Guards against double-processing (only pending may be rejected).
 *  - Sets status = rejected, rejection_reason and rejected_at.
 *
 * Runs inside a single database transaction. No payouts are generated and no
 * role is granted.
 */
class RejectInvestment
{
    public function execute(Investment $investment, ?string $reason = null): Investment
    {
        if ($investment->status !== InvestmentStatus::Pending) {
            throw InvestmentAlreadyProcessedException::forInvestment($investment->id);
        }

        return DB::transaction(function () use ($investment, $reason): Investment {
            $investment->forceFill([
                'status' => InvestmentStatus::Rejected,
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ])->save();

            $investment->loadMissing('contract');
            $investment->user->notify(new InvestmentRejectedNotification($investment, $reason));

            return $investment;
        });
    }
}
