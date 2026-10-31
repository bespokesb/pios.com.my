<?php

namespace App\Policies;

use App\Models\SubscriptionPayment;
use App\Models\User;

class SubscriptionPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, SubscriptionPayment $subscriptionPayment): bool
    {
        if ($user->isSysAdmin() || $user->isPiosOwner()) {
            return true;
        }

        if ($subscriptionPayment->user_id === $user->id) {
            return true;
        }

        if ($user->isPartner() && $user->partner && $user->partner->id === $subscriptionPayment->partner_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, SubscriptionPayment $subscriptionPayment): bool
    {
        if ($user->isSysAdmin() || $user->isPiosOwner()) {
            return true;
        }

        // Sponsoring partner can update / approve payments made by their BOs
        if ($user->isPartner() && $user->partner && $user->partner->id === $subscriptionPayment->partner_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, SubscriptionPayment $subscriptionPayment): bool
    {
        return $user->isSysAdmin() || $user->isPiosOwner();
    }
}
