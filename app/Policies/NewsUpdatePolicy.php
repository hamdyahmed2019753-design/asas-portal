<?php

namespace App\Policies;

use App\Models\NewsUpdate;
use App\Models\User;

class NewsUpdatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, NewsUpdate $newsUpdate): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, NewsUpdate $newsUpdate): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, NewsUpdate $newsUpdate): bool
    {
        return $user->hasRole('admin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
