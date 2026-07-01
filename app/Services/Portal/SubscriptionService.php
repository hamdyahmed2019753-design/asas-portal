<?php

namespace App\Services\Portal;

use App\Actions\Admin\NotifyAdmins;
use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\ContractStatus;
use App\Enums\DocumentCategory;
use App\Enums\InvestmentStatus;
use App\Filament\Resources\InvestmentResource;
use App\Models\Contract;
use App\Models\Investment;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Http\UploadedFile;

/**
 * The direct bank-transfer subscription workflow. An investor picks a number of
 * shares (priced by the contract), a pending-payment investment is created, they
 * transfer to a configured bank account and upload the receipt; the admin then
 * confirms + approves. All reads/writes are scoped to the acting user.
 */
class SubscriptionService
{
    /**
     * A contract accepts direct subscription only when it has a share price and
     * is open.
     */
    public function subscribable(Contract $contract): bool
    {
        return $contract->share_price !== null
            && (float) $contract->share_price > 0
            && $contract->status === ContractStatus::Open;
    }

    /**
     * Create the pending-payment investment (amount = shares × share price).
     */
    public function subscribe(User $user, Contract $contract, int $shares): Investment
    {
        return $user->investments()->create([
            'contract_id' => $contract->id,
            'shares' => $shares,
            'amount' => round((float) $contract->share_price * $shares, 2),
            'status' => InvestmentStatus::PendingPayment->value,
        ]);
    }

    /**
     * Store the investor's transfer receipt (private disk), move the investment to
     * "payment submitted", surface it in the documents center, and alert admins.
     */
    public function submitReceipt(User $user, Investment $investment, UploadedFile $file): void
    {
        $investment->loadMissing('contract');
        $path = $file->store("receipts/{$user->id}", 'local');

        $investment->forceFill([
            'receipt_path' => $path,
            'status' => InvestmentStatus::PaymentSubmitted->value,
        ])->save();

        $user->documents()->create([
            'category' => DocumentCategory::PaymentReceipt->value,
            'title' => 'إيصال تحويل — '.($investment->contract?->title ?? 'عقد')." #{$investment->id}",
            'disk' => 'local',
            'path' => $path,
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ]);

        NotifyAdmins::send(new AdminNotification(
            title: 'طلب اشتراك بانتظار الاعتماد',
            body: "رفع «{$user->name}» إيصال تحويل لعقد «{$investment->contract?->title}» بمبلغ ".money($investment->amount).'.',
            category: AdminNotificationCategory::Investment,
            priority: AdminNotificationPriority::High,
            actor: $user,
            target: $investment,
            url: InvestmentResource::getUrl('index'),
            actionLabel: 'مراجعة الطلب',
        ));
    }

    /**
     * Configured bank accounts (only those with a bank name set).
     *
     * @return array<int, array{name: ?string, account_name: ?string, iban: ?string}>
     */
    public function bankAccounts(): array
    {
        return collect([1, 2])
            ->map(fn (int $i): array => [
                'name' => setting("bank.{$i}.name"),
                'account_name' => setting("bank.{$i}.account_name"),
                'iban' => setting("bank.{$i}.iban"),
            ])
            ->filter(fn (array $bank): bool => filled($bank['name']))
            ->values()
            ->all();
    }

    /**
     * Allowed shares range derived from the contract's amount limits and price.
     *
     * @return array{min: int, max: ?int}
     */
    public function shareBounds(Contract $contract): array
    {
        $price = (float) $contract->share_price;

        if ($price <= 0) {
            return ['min' => 1, 'max' => null];
        }

        return [
            'min' => max(1, (int) ceil((float) $contract->min_amount / $price)),
            'max' => $contract->max_amount !== null ? (int) floor((float) $contract->max_amount / $price) : null,
        ];
    }
}
