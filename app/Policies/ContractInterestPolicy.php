<?php

namespace App\Policies;

use App\Models\ContractInterest;
use App\Models\User;

class ContractInterestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, ContractInterest $interest): bool
    {
        return $user->hasRole('admin') || (int) $interest->user_id === (int) $user->id;
    }

    /**
     * Expressing interest is an investment action — gated on approved KYC.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->kycApproved();
    }
}
