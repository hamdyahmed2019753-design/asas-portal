<?php

namespace App\Console\Commands;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Notifications\PayoutDueNotification;
use Illuminate\Console\Command;

/**
 * Daily internal job: promote scheduled payouts whose due date has arrived to
 * the "due" status. No external sending — only updates internal state used by
 * the dashboard counters and "next payout" indicators.
 */
class RefreshPayouts extends Command
{
    protected $signature = 'payouts:refresh';

    protected $description = 'Mark scheduled payouts whose due date has arrived as due.';

    public function handle(): int
    {
        // Eager-load the owner so notifying each due payout stays N+1-free.
        $payouts = Payout::query()
            ->where('status', PayoutStatus::Scheduled->value)
            ->whereDate('due_date', '<=', now()->toDateString())
            ->with('investment.user')
            ->get();

        foreach ($payouts as $payout) {
            $payout->forceFill(['status' => PayoutStatus::Due->value])->save();
            $payout->investment?->user?->notify(new PayoutDueNotification($payout));
        }

        $count = $payouts->count();
        $this->info("Refreshed {$count} payout(s) to due.");

        return self::SUCCESS;
    }
}
