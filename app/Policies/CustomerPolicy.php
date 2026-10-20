<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, Customer $customer): bool
    {
        return Customer::query()->visibleTo($user)->whereKey($customer->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isBusinessOwner() || $user->isPartner();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $customer->business_owner_id)
            || $this->ownsPartnerDirectCustomer($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $customer->business_owner_id)
            || $this->ownsPartnerDirectCustomer($user, $customer);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * A Partner may fully manage a customer they own directly (one with no
     * Business Owner). Business-Owner-owned customers in their downline stay
     * read-only to the Partner except for VIP tagging — see tagVip().
     */
    private function ownsPartnerDirectCustomer(User $user, Customer $customer): bool
    {
        return $user->isPartner()
            && $customer->business_owner_id === null
            && $user->partner?->id === $customer->partner_id;
    }

    /**
     * Tag a customer's LOS/VIP status. Reserved for the account manager (the
     * Partner over the owning Business Owner) and HQ — a Business Owner cannot
     * self-certify a customer as VIP (LOS verification sits above them).
     */
    public function tagVip(User $user, Customer $customer): bool
    {
        return $user->isAdmin()
            || ($user->isPartner() && $user->partner?->id === $customer->partner_id);
    }

    /**
     * Apply free-form labels/tags. Both the owning Business Owner and the
     * managing Partner (and HQ) may tag a customer they are responsible for —
     * even a Partner tagging a downstream BO-owned customer they cannot fully
     * edit.
     */
    public function tagLabels(User $user, Customer $customer): bool
    {
        return $user->isAdmin()
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $customer->business_owner_id)
            || ($user->isPartner() && $user->partner?->id === $customer->partner_id);
    }
}
