<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View/download a document: the owner, or an admin.
     */
    public function view(User $user, Document $document): bool
    {
        return (int) $user->id === (int) $document->user_id || $user->hasRole('admin');
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }
}
