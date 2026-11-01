<?php

namespace App\Models;

use App\Casts\PhoneNumber;
use App\Enums\RecordStatus;
use App\Services\IdentifierGenerator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'partner_id',
    'territory_id',
    'business_owner_no',
    'applicant_name',
    'ic_no',
    'hp_no',
    'email',
    'address',
    'address_line_2',
    'address_line_3',
    'bandar',
    'negeri',
    'poskod',
    'spouse_name',
    'spouse_ic_no',
    'spouse_hp_no',
    'spouse_email',
    'company_name',
    'is_amway_agent',
    'product_description',
    'status',
    'ustazai_api_key',
    'ustazai_sender',
    'whatsapp_provider',
    'whatsapp_api_token',
    'whatsapp_instance_id',
    'whatsapp_status',
    'whatsapp_last_tested_at',
    'created_by',
])]
class BusinessOwner extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (): void {
            app(IdentifierGenerator::class)->sync('business_owner', static::class, 'business_owner_no');
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->actsAsPartner() && $user->partner) {
            return $query->where('partner_id', $user->partner->id);
        }

        if ($user->actsAsBusinessOwner() && $user->businessOwner) {
            return $query->whereKey($user->businessOwner->id);
        }

        // Dual-role, no active panel: the partner's managed BOs plus their own BO record.
        if ($user->hasPartnerProfile() && $user->partner
            && $user->hasBusinessOwnerProfile() && $user->businessOwner) {
            return $query->where(function (Builder $q) use ($user): void {
                $q->where('partner_id', $user->partner->id)
                    ->orWhere('id', $user->businessOwner->id);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function productCount(): int
    {
        return $this->products()->count();
    }

    public function hasReachedProductLimit(): bool
    {
        return $this->productCount() >= Product::MAX_PER_BUSINESS_OWNER;
    }

    public function productSelections(): HasMany
    {
        return $this->hasMany(BusinessOwnerProductSelection::class);
    }

    public function productProposals(): HasMany
    {
        return $this->hasMany(ProductProposal::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isWhatsAppConfigured(): bool
    {
        if ($this->whatsapp_provider === 'manual') {
            return true;
        }

        if ($this->whatsapp_provider === 'fonnte') {
            return filled($this->whatsapp_api_token);
        }

        if ($this->whatsapp_provider === 'ultramsg') {
            return filled($this->whatsapp_api_token) && filled($this->whatsapp_instance_id);
        }

        return false;
    }

    public function whatsAppProviderLabel(): string
    {
        return match ($this->whatsapp_provider) {
            'fonnte' => 'Fonnte (API)',
            'ultramsg' => 'UltraMsg (API)',
            default => 'Manual (wa.me)',
        };
    }

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'is_amway_agent' => 'boolean',
            'hp_no' => PhoneNumber::class,
            'spouse_hp_no' => PhoneNumber::class,
            'ustazai_api_key' => 'encrypted',
            'whatsapp_api_token' => 'encrypted',
            'whatsapp_last_tested_at' => 'datetime',
        ];
    }
}
