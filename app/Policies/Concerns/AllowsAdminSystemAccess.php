<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AllowsAdminSystemAccess
{
    protected function canManageSystem(User $user): bool
    {
        return $user->role?->canManageSystem() ?? false;
    }

    public function viewAny(User $user): bool
    {
        return $this->canManageSystem($user);
    }

    public function view(User $user): bool
    {
        return $this->canManageSystem($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSystem($user);
    }

    public function update(User $user): bool
    {
        return $this->canManageSystem($user);
    }

    public function delete(User $user): bool
    {
        return $this->canManageSystem($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canManageSystem($user);
    }
}
