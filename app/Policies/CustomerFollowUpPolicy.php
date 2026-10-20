<?php

namespace App\Policies;

use App\Models\CustomerFollowUp;
use App\Models\User;

class CustomerFollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, CustomerFollowUp $followUp): bool
    {
        return CustomerFollowUp::query()->visibleTo($user)->whereKey($followUp->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isBusinessOwner();
    }

    public function update(User $user, CustomerFollowUp $followUp): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $followUp->business_owner_id);
    }

    public function delete(User $user, CustomerFollowUp $followUp): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $followUp->business_owner_id);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
