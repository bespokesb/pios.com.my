<?php

namespace App\Policies;

use App\Enums\ProductProposalStatus;
use App\Models\ProductProposal;
use App\Models\User;

class ProductProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, ProductProposal $productProposal): bool
    {
        return ProductProposal::query()->visibleTo($user)->whereKey($productProposal->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isBusinessOwner();
    }

    public function update(User $user, ProductProposal $productProposal): bool
    {
        return $user->isAdmin()
            || (
                $user->isBusinessOwner()
                && $user->businessOwner?->id === $productProposal->business_owner_id
                && $productProposal->approval_status === ProductProposalStatus::Pending
            );
    }

    public function delete(User $user, ProductProposal $productProposal): bool
    {
        return $this->update($user, $productProposal);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
