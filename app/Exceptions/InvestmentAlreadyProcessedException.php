<?php

namespace App\Exceptions;

use DomainException;

/**
 * Thrown when an attempt is made to approve (or otherwise process) an
 * investment that is no longer in the pending state.
 */
class InvestmentAlreadyProcessedException extends DomainException
{
    public static function forInvestment(int $investmentId): self
    {
        return new self("Investment [{$investmentId}] has already been processed.");
    }
}
