<?php

namespace App\Policies;

use App\Models\BusinessOwnerProductSelection;
use App\Models\User;

class BusinessOwnerProductSelectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, BusinessOwnerProductSelection $businessOwnerProductSelection): bool
    {
        return BusinessOwnerProductSelection::query()
            ->visibleTo($user)
            ->whereKey($businessOwnerProductSelection->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isBusinessOwner();
    }

    public function update(User $user, BusinessOwnerProductSelection $businessOwnerProductSelection): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $businessOwnerProductSelection->business_owner_id);
    }

    public function delete(User $user, BusinessOwnerProductSelection $businessOwnerProductSelection): bool
    {
        return $this->update($user, $businessOwnerProductSelection);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
