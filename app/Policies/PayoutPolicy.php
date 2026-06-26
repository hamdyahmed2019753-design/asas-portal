<?php

namespace App\Policies;

use App\Models\Payout;
use App\Models\User;

class PayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Payout $payout): bool
    {
        return $user->hasRole('admin');
    }

    // Payouts are generated automatically on approval, never created by hand.
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Payout $payout): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Payout $payout): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
