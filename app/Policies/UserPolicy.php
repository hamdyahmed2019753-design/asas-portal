<?php

namespace App\Policies;

use App\Enums\KycState;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    // Investors register through the portal, not the admin panel.
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Who may download a user's private KYC documents: the owner, or an admin.
     */
    public function viewKycDocuments(User $user, User $model): bool
    {
        return (int) $user->id === (int) $model->id || $user->hasRole('admin');
    }

    /**
     * Only admins may review (approve/reject) KYC submissions.
     */
    public function reviewKyc(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * A user may re-upload KYC documents only for themselves, and only when
     * their previous submission was rejected.
     */
    public function resubmitKyc(User $user, User $model): bool
    {
        return (int) $user->id === (int) $model->id && $user->kyc_state === KycState::Rejected;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
