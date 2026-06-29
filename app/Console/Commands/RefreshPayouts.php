<?php

namespace App\Console\Commands;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\PayoutStatus;
use App\Filament\Resources\PayoutResource;
use App\Models\Payout;
use App\Notifications\Admin\AdminNotification;
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

            $investorName = $payout->investment?->user?->name ?? '—';
            $dueDate = $payout->due_date?->format('Y-m-d');

            NotifyAdmins::send(new AdminNotification(
                title: 'توزيعة مستحقة جديدة',
                body: "أصبحت توزيعة المستثمر «{$investorName}» مستحقة ({$dueDate}).",
                category: AdminNotificationCategory::Payout,
                priority: AdminNotificationPriority::High,
                actor: $payout->investment?->user,
                target: $payout,
                url: PayoutResource::getUrl('index'),
                actionLabel: 'فتح التوزيعة',
            ));
        }

        $count = $payouts->count();
        $this->info("Refreshed {$count} payout(s) to due.");

        return self::SUCCESS;
    }
}
