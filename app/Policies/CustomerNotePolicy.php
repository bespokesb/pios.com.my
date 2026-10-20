<?php

namespace App\Policies;

use App\Models\CustomerNote;
use App\Models\User;

class CustomerNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, CustomerNote $note): bool
    {
        return CustomerNote::query()->visibleTo($user)->whereKey($note->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isBusinessOwner();
    }

    public function update(User $user, CustomerNote $note): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $note->business_owner_id);
    }

    public function delete(User $user, CustomerNote $note): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $note->business_owner_id);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
