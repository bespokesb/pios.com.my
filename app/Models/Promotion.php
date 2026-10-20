<?php

namespace App\Models;

use App\Enums\PromotionMode;
use App\Enums\PromotionStatus;
use App\Models\Concerns\ScopesByOwnerVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_owner_id',
    'partner_id',
    'product_id',
    'mode',
    'starts_at',
    'ends_at',
    'discount_type',
    'discount_value',
    'offer_terms',
    'photo_1_path',
    'photo_2_path',
    'max_customers',
    'status',
    'ran_at',
    'created_by',
])]
class Promotion extends Model
{
    use ScopesByOwnerVisibility;

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PromotionCustomer::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'promotion_customers')
            ->withPivot(['status', 'sent_at', 'responded_at'])
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'ends_at' => 'datetime',
            'mode' => PromotionMode::class,
            'ran_at' => 'datetime',
            'starts_at' => 'datetime',
            'status' => PromotionStatus::class,
        ];
    }
}
