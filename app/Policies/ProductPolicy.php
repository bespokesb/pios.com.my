<?php

namespace App\Policies;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isPartner() || $user->isBusinessOwner();
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBusinessOwner()) {
            return $this->ownsProduct($user, $product)
                || $product->status === ProductStatus::Active;
        }

        return $this->viewAny($user)
            && $product->status === ProductStatus::Active;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBusinessOwner()) {
            return $user->businessOwner !== null && ! $user->businessOwner->hasReachedProductLimit();
        }

        return false;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || $this->ownsProduct($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin() || $this->ownsProduct($user, $product);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function share(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    private function ownsProduct(User $user, Product $product): bool
    {
        return $user->isBusinessOwner()
            && $product->business_owner_id !== null
            && $product->business_owner_id === $user->businessOwner?->id;
    }
}
