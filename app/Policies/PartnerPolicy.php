<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, Partner $partner): bool
    {
        return Partner::query()->visibleTo($user)->whereKey($partner->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner();
    }

    public function approve(User $user, Partner $partner): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Partner $partner): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
