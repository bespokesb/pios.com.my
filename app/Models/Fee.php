<?php

namespace App\Models;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Models\Concerns\ScopesByOwnerVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['partner_id', 'business_owner_id', 'customer_id', 'fee_type', 'billing_period', 'amount', 'due_date', 'status'])]
class Fee extends Model
{
    use ScopesByOwnerVisibility;

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'fee_type' => FeeType::class,
            'status' => FeeStatus::class,
        ];
    }
}
