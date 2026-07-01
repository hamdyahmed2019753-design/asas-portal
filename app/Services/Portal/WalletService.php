<?php

namespace App\Services\Portal;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionReason;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The investor cash wallet. Balance is always derived from the append-only
 * wallet_transactions ledger (credits − debits) — never stored — so it can never
 * drift. Every read/write is scoped to the given user.
 */
class WalletService
{
    public function balance(User $user): float
    {
        $sums = $user->walletTransactions()
            ->selectRaw('direction, SUM(amount) as total')
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return (float) ($sums[WalletTransactionDirection::Credit->value] ?? 0)
             - (float) ($sums[WalletTransactionDirection::Debit->value] ?? 0);
    }

    public function credit(User $user, float $amount, WalletTransactionReason $reason, ?Model $reference = null, ?string $note = null): WalletTransaction
    {
        return $this->record($user, WalletTransactionDirection::Credit, $amount, $reason, $reference, $note);
    }

    public function debit(User $user, float $amount, WalletTransactionReason $reason, ?Model $reference = null, ?string $note = null): WalletTransaction
    {
        return $this->record($user, WalletTransactionDirection::Debit, $amount, $reason, $reference, $note);
    }

    /**
     * @return Collection<int, WalletTransaction>
     */
    public function transactions(User $user, int $limit = 50): Collection
    {
        return $user->walletTransactions()->with('reference')->latest()->limit($limit)->get();
    }

    private function record(User $user, WalletTransactionDirection $direction, float $amount, WalletTransactionReason $reason, ?Model $reference, ?string $note): WalletTransaction
    {
        return $user->walletTransactions()->create([
            'direction' => $direction->value,
            'amount' => round($amount, 2),
            'reason' => $reason->value,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'note' => $note,
        ]);
    }
}
