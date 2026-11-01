<?php

namespace App\Policies;

use App\Models\SubscriptionProduct;
use App\Models\User;

class SubscriptionProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, SubscriptionProduct $subscriptionProduct): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isSysAdmin() || $user->isPiosOwner();
    }

    public function update(User $user, SubscriptionProduct $subscriptionProduct): bool
    {
        return $user->isSysAdmin() || $user->isPiosOwner();
    }

    public function delete(User $user, SubscriptionProduct $subscriptionProduct): bool
    {
        return $user->isSysAdmin() || $user->isPiosOwner();
    }
}
