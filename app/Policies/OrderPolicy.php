<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, Order $order): bool
    {
        return Order::query()->visibleTo($user)->whereKey($order->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isBusinessOwner();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $order->business_owner_id);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
