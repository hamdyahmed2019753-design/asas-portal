<?php

namespace App\Policies;

use App\Models\Investment;
use App\Models\User;

class InvestmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Investment $investment): bool
    {
        // Admins (panel) or the investment's owner (portal) may view it.
        return $user->hasRole('admin') || $investment->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Admins always; investors only once their KYC is approved.
        return $user->hasRole('admin') || $user->kycApproved();
    }

    public function update(User $user, Investment $investment): bool
    {
        return $user->hasRole('admin');
    }

    // Investments are never deleted — they carry approval / payout history.
    public function delete(User $user, Investment $investment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
