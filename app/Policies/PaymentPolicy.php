<?php

namespace App\Policies;

use App\Models\Fee;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return ($payment->fee_id && Fee::query()->visibleTo($user)->whereKey($payment->fee_id)->exists())
            || ($payment->order_id && Order::query()->visibleTo($user)->whereKey($payment->order_id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
