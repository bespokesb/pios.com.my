<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return Promotion::query()->visibleTo($user)->whereKey($promotion->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isBusinessOwner();
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $promotion->business_owner_id);
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
