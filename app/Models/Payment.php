<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fee_id', 'order_id', 'payment_type', 'amount', 'status', 'reference_no', 'paid_at', 'recorded_by'])]
class Payment extends Model
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->whereHas('fee', fn (Builder $query): Builder => $query->visibleTo($user))
                ->orWhereHas('order', fn (Builder $query): Builder => $query->visibleTo($user));
        });
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'status' => PaymentStatus::class,
        ];
    }
}
