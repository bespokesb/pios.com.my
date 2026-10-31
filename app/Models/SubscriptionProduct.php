<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class SubscriptionProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'period_days',
        'target_role',
        'is_mandatory',
        'is_active',
        'qr_code_image',
        'payment_instructions',
        'sort_order',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'period_days' => 'integer',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Scope only active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope products available for a specific role ('partner' or 'business_owner').
     */
    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->whereIn('target_role', ['all', $role]);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Resolve public URL for QR Code image.
     */
    public function qrCodeUrl(): ?string
    {
        if (! filled($this->qr_code_image)) {
            return null;
        }

        if (str_starts_with($this->qr_code_image, 'http://') || str_starts_with($this->qr_code_image, 'https://')) {
            return $this->qr_code_image;
        }

        return Storage::disk('public')->url($this->qr_code_image);
    }

    public function targetRoleLabel(): string
    {
        return match ($this->target_role) {
            'partner' => 'Partner Sahaja',
            'business_owner' => 'Business Owner Sahaja',
            default => 'Semua (Partner & BO)',
        };
    }
}
