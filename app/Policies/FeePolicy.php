<?php

namespace App\Policies;

use App\Models\Fee;
use App\Models\User;

class FeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, Fee $fee): bool
    {
        return Fee::query()->visibleTo($user)->whereKey($fee->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Fee $fee): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Fee $fee): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
