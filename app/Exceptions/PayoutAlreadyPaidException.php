<?php

namespace App\Exceptions;

use DomainException;

/**
 * Thrown when attempting to mark a payout as paid that is already paid.
 */
class PayoutAlreadyPaidException extends DomainException
{
    public static function forPayout(int $payoutId): self
    {
        return new self("Payout [{$payoutId}] is already marked as paid.");
    }
}
