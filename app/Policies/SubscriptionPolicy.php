<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Subscription $subscription): bool
    {
        if ($user->isSysAdmin() || $user->isPiosOwner()) {
            return true;
        }

        if ($subscription->user_id === $user->id) {
            return true;
        }

        if ($user->isPartner() && $user->partner && $user->partner->id === $subscription->partner_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->isSysAdmin() || $user->isPiosOwner();
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->isSysAdmin() || $user->isPiosOwner();
    }
}

