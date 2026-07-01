<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $user->hasRole('admin') || (int) $withdrawal->user_id === (int) $user->id;
    }
}
