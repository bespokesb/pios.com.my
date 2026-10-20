<?php

namespace App\Models;

use App\Casts\PhoneNumber;
use App\Enums\CustomerCategory;
use App\Enums\RecordStatus;
use App\Models\Concerns\ScopesByOwnerVisibility;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_owner_id',
    'partner_id',
    'customer_no',
    'category',
    'is_vip',
    'name',
    'nick_name',
    'contact_person',
    'hp_no',
    'hp_no_normalized',
    'kp_no',
    'email',
    'age',
    'marital_status',
    'occupation',
    'remarks',
    'address',
    'address_line_2',
    'address_line_3',
    'bandar',
    'negeri',
    'poskod',
    'abo_no',
    'dd_name',
    'dd_hp_no',
    'status',
    'tags',
    'created_by',
])]
class Customer extends Model
{
    use HasFactory;
    use ScopesByOwnerVisibility;

    /**
     * Keep the indexed, digits-only phone in sync on every write so customer
     * lookup/duplicate-detection by phone is an indexed equality match rather
     * than an unbounded in-memory scan.
     */
    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            if ($customer->isDirty('hp_no')) {
                // hp_no is already canonicalised by the PhoneNumber cast; keep the
                // indexed lookup key in the same canonical form.
                $customer->hp_no_normalized = Phone::toWhatsApp($customer->hp_no) ?: null;
            }
        });
    }

    /**
     * Map contact_person to nick_name for business customer registrations.
     */
    public function getContactPersonAttribute(): ?string
    {
        return $this->nick_name;
    }

    public function setContactPersonAttribute(?string $value): void
    {
        $this->attributes['nick_name'] = $value;
    }

    /**
     * Product eligibility is unrestricted: with the Amway/Non-Amway classification
     * removed, every customer may buy every product. Retained as the single
     * extension point that Orders and Promotions route through, so any future
     * eligibility rule lands here and nowhere else.
     */
    public function canBuyProduct(Product $product): bool
    {
        return true;
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(CustomerFollowUp::class);
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_customers')
            ->withPivot(['status', 'sent_at', 'responded_at'])
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'category' => CustomerCategory::class,
            'is_vip' => 'boolean',
            'age' => 'integer',
            'hp_no' => PhoneNumber::class,
            'dd_hp_no' => PhoneNumber::class,
            'status' => RecordStatus::class,
            'tags' => 'array',
        ];
    }
}
