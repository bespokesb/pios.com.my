<?php

namespace App\Models;

use App\Casts\PhoneNumber;
use App\Enums\RecordStatus;
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
    'created_by',
])]
class BusinessOwner extends Model
{
    use HasFactory;

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

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'is_amway_agent' => 'boolean',
            'hp_no' => PhoneNumber::class,
            'spouse_hp_no' => PhoneNumber::class,
            'ustazai_api_key' => 'encrypted',
        ];
    }
}
