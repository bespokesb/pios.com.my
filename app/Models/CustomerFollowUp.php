<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'business_owner_id',
    'note',
    'due_on',
    'completed_at',
    'created_by',
])]
class CustomerFollowUp extends Model
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isBusinessOwner() && $user->businessOwner) {
            return $query->where('business_owner_id', $user->businessOwner->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('completed_at')
            ->whereDate('due_on', '<', now('Asia/Kuala_Lumpur')->toDateString());
    }

    public function isOverdue(): bool
    {
        if ($this->completed_at !== null || $this->due_on === null) {
            return false;
        }

        return $this->due_on->startOfDay()->lt(now('Asia/Kuala_Lumpur')->startOfDay());
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
