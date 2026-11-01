<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_no',
        'subscription_product_id',
        'user_id',
        'subscriber_type',
        'partner_id',
        'business_owner_id',
        'status',
        'starts_at',
        'expires_at',
        'last_renewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_renewed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SubscriptionProduct::class, 'subscription_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(SubscriptionPayment::class)->latestOfMany();
    }

    public function isActive(): bool
    {
        if (! in_array($this->status, ['active', 'trial'], true)) {
            return false;
        }

        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->isActive();
    }

    public function isExpired(): bool
    {
        return ! $this->isActive();
    }

    public function hoursRemaining(): int
    {
        if (! $this->isActive() || ! $this->expires_at) {
            return 0;
        }

        return max(0, (int) now()->diffInHours($this->expires_at, false));
    }

    public function daysRemaining(): int
    {
        if (! $this->isActive() || ! $this->expires_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public function subscriberName(): string
    {
        if ($this->businessOwner) {
            return $this->businessOwner->applicant_name ?: ($this->user?->name ?? 'Business Owner');
        }

        if ($this->partner) {
            return $this->partner->applicant_name ?: ($this->user?->name ?? 'Partner');
        }

        return $this->user?->name ?? '-';
    }

    public function subscriberIdentifier(): string
    {
        if ($this->businessOwner) {
            return $this->businessOwner->business_owner_no ?: ('BO#'.$this->businessOwner->id);
        }

        if ($this->partner) {
            return $this->partner->partner_no ?: ('P#'.$this->partner->id);
        }

        return '-';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
