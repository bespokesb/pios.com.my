<?php

namespace App\Policies;

use App\Enums\RecordStatus;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;

class BusinessOwnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, BusinessOwner $businessOwner): bool
    {
        return BusinessOwner::query()->visibleTo($user)->whereKey($businessOwner->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner();
    }

    public function approve(User $user, BusinessOwner $businessOwner): bool
    {
        return $user->isAdmin();
    }

    /**
     * Initiate a BO → Partner upgrade. Reserved for HQ or the BO's managing
     * partner, and only for an active BO whose user is not already a Partner.
     */
    public function initiatePartnerUpgrade(User $user, BusinessOwner $businessOwner): bool
    {
        if ($businessOwner->status !== RecordStatus::Active || blank($businessOwner->user_id)) {
            return false;
        }

        if (Partner::query()->where('user_id', $businessOwner->user_id)->exists()) {
            return false; // already a Partner / upgrade pending
        }

        return $user->isAdmin()
            || ($user->actsAsPartner() && $user->partner?->id === $businessOwner->partner_id);
    }

    public function update(User $user, BusinessOwner $businessOwner): bool
    {
        return $user->isAdmin()
            || ($user->isPartner() && $user->partner?->id === $businessOwner->partner_id)
            || ($user->isBusinessOwner() && $user->businessOwner?->id === $businessOwner->id);
    }

    /**
     * Verify whether a Business Owner is an Amway agent. Reserved for the BO's
     * managing Partner and HQ — a Business Owner can never self-certify (which is
     * why this is NOT update(), where a BO may edit their own record).
     */
    public function verifyAmwayAgent(User $user, BusinessOwner $businessOwner): bool
    {
        return $user->isAdmin()
            || ($user->isPartner() && $user->partner?->id === $businessOwner->partner_id);
    }

    public function delete(User $user, BusinessOwner $businessOwner): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
