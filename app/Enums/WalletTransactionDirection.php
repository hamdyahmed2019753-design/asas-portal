<?php

namespace App\Enums;

enum WalletTransactionDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'إيداع',
            self::Debit => 'خصم',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Credit => 'success',
            self::Debit => 'danger',
        };
    }

    /**
     * Signed multiplier used when summing the ledger into a balance.
     */
    public function sign(): int
    {
        return $this === self::Credit ? 1 : -1;
    }
}
