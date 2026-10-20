<?php

namespace App\Models;

use App\Enums\ProductSelectionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['business_owner_id', 'product_id', 'status', 'selected_at'])]
class BusinessOwnerProductSelection extends Model
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('businessOwner', fn (Builder $query): Builder => $query->visibleTo($user));
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'selected_at' => 'datetime',
            'status' => ProductSelectionStatus::class,
        ];
    }
}
