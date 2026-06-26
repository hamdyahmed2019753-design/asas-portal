<?php

namespace App\Exceptions;

use DomainException;

/**
 * Thrown when attempting to pay a profit payout that has no amount set yet.
 * Profit amounts are entered manually by the administration before payment.
 */
class PayoutAmountMissingException extends DomainException
{
    public static function forPayout(int $payoutId): self
    {
        return new self("Profit payout [{$payoutId}] cannot be paid without an amount.");
    }
}
