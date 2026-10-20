<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Panel-aware ownership visibility for models that carry BOTH a partner_id and a
 * business_owner_id (Customer, Order, Promotion, Fee).
 *
 * A single-role user's acting hat always equals their capability, so the result
 * is identical to the previous inline scope. Only a genuinely dual-role user
 * (holds both a Partner and a BusinessOwner profile) diverges: they see their
 * partner downstream in /partner and their own BO records in /business, and the
 * union of both only in a panel-less context (console / scheduled jobs / tests).
 */
trait ScopesByOwnerVisibility
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->actsAsPartner() && $user->partner) {
            return $query->where('partner_id', $user->partner->id);
        }

        if ($user->actsAsBusinessOwner() && $user->businessOwner) {
            return $query->where('business_owner_id', $user->businessOwner->id);
        }

        // Dual-role with no active panel: union both hats.
        if ($user->hasPartnerProfile() && $user->partner
            && $user->hasBusinessOwnerProfile() && $user->businessOwner) {
            return $query->where(function (Builder $q) use ($user): void {
                $q->where('partner_id', $user->partner->id)
                    ->orWhere('business_owner_id', $user->businessOwner->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
