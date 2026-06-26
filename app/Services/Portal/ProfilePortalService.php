<?php

namespace App\Services\Portal;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Models\User;

/**
 * All queries for the investor profile page. Everything is read from the
 * authenticated user's own relationships — never a global query.
 */
class ProfilePortalService
{
    public function __construct(private readonly KycService $kyc) {}

    /**
     * Profile payload: the user, their KYC card, and account statistics.
     *
     * @return array<string, mixed>
     */
    public function data(User $user): array
    {
        return [
            'user' => $user,
            'kyc' => $this->kyc->card($user),
            'stats' => [
                'investments' => $user->investments()->approved()->count(),
                'capital' => (float) $user->investments()->approved()->sum('amount'),
                'profit' => (float) $user->payouts()
                    ->where('payouts.type', PayoutType::Profit->value)
                    ->where('payouts.status', PayoutStatus::Paid->value)
                    ->sum('payouts.amount'),
                'payouts' => $user->payouts()->count(),
            ],
        ];
    }
}
