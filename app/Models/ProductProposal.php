<?php

namespace App\Models;

use App\Enums\ProductProposalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_owner_id',
    'name',
    'area',
    'cost_price',
    'partner_price',
    'customer_price',
    'image_video_path',
    'media_assets',
    'video_path',
    'stock_in_hand',
    'stock_added',
    'stock_date_added',
    'description',
    'approval_status',
    'reviewed_by',
    'reviewed_at',
])]
class ProductProposal extends Model
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('businessOwner', fn (Builder $query): Builder => $query->visibleTo($user));
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return [
            'approval_status' => ProductProposalStatus::class,
            'cost_price' => 'decimal:2',
            'customer_price' => 'decimal:2',
            'partner_price' => 'decimal:2',
            'stock_in_hand' => 'integer',
            'stock_added' => 'integer',
            'stock_date_added' => 'date',
            'media_assets' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }
}
