<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'product_type_id',
    'territory_id',
    'business_owner_id',
    'vendor_id',
    'product_no',
    'name',
    'description',
    'selling_price',
    'stock_quantity',
    'cost',
    'coverage_area',
    'certification',
    'promotional_content',
    'media_assets',
    'video_path',
    'pricing_structure',
    'sales_commission',
    'status',
    'created_by',
])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const MAX_PER_BUSINESS_OWNER = 20;

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        // Business Owners only see (and manage) products they own; a BO without
        // a linked profile sees nothing (symmetry with Customer::scopeVisibleTo).
        // Acting hat: a dual-role user in /business manages their own products;
        // in /partner (or console) they see the shared active catalog.
        if ($user->actsAsBusinessOwner()) {
            return $user->businessOwner
                ? $query->where('business_owner_id', $user->businessOwner->id)
                : $query->whereRaw('1 = 0');
        }

        return $query->where('status', ProductStatus::Active->value);
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function selections(): HasMany
    {
        return $this->hasMany(BusinessOwnerProductSelection::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Build the customer-facing WhatsApp share message.
     *
     * By construction this only references name, selling_price, description and
     * media_assets — it can NEVER leak cost, vendor, margin, sales_commission or
     * pricing_structure.
     */
    public function shareMessage(): string
    {
        $lines = [
            __('pios.product.share.intro'),
            $this->name,
            __('pios.product.share.price', ['price' => number_format((float) $this->selling_price, 2)]),
        ];

        if (filled($this->description)) {
            $lines[] = '';
            $lines[] = $this->description;
        }

        // The public product link is the heart of the WhatsApp blast — it carries
        // the buyer to the order page. Only meaningful once the product has a No.
        if (filled($this->product_no)) {
            $lines[] = '';
            $lines[] = $this->publicUrl();
        }

        return implode("\n", $lines);
    }

    public function shareWhatsAppUrl(): string
    {
        return 'https://wa.me/?text='.rawurlencode($this->shareMessage());
    }

    /**
     * Public, shareable URL for the customer-facing product page.
     */
    public function publicUrl(): string
    {
        return route('public.product.show', ['product' => $this->product_no]);
    }

    /**
     * Whether this product may be both viewed AND ordered on the public page.
     * Any active product with an owning Business Owner is publicly orderable.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->status === ProductStatus::Active
            && $this->business_owner_id !== null;
    }

    public function isPubliclyOrderable(): bool
    {
        return $this->isPubliclyVisible();
    }

    /**
     * All shareable image URLs (public disk), filtered to real image files.
     *
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return collect($this->media_assets ?? [])
            ->filter(fn ($asset): bool => is_string($asset) && $this->isImageAsset($asset))
            ->map(fn (string $asset): string => $this->resolveMediaUrl($asset))
            ->values()
            ->all();
    }

    public function firstShareableImageUrl(): ?string
    {
        foreach ($this->media_assets ?? [] as $asset) {
            if (is_string($asset) && $this->isImageAsset($asset)) {
                return $this->resolveMediaUrl($asset);
            }
        }

        return null;
    }

    /**
     * Resolves the primary product video URL if a video was uploaded.
     */
    public function videoUrl(): ?string
    {
        if (filled($this->video_path)) {
            return $this->resolveMediaUrl($this->video_path);
        }

        foreach ($this->media_assets ?? [] as $asset) {
            if (is_string($asset) && $this->isVideoAsset($asset)) {
                return $this->resolveMediaUrl($asset);
            }
        }

        return null;
    }

    public function hasVideo(): bool
    {
        return $this->videoUrl() !== null;
    }

    public function isVideoAsset(string $asset): bool
    {
        return preg_match('/\.(mp4|webm|mov|ogg|m4v)$/i', $asset) === 1;
    }

    /**
     * An asset counts as an image if it is an absolute URL (a remote/CDN image)
     * or a local storage path ending in an image extension.
     */
    private function isImageAsset(string $asset): bool
    {
        return $this->isAbsoluteUrl($asset)
            || preg_match('/\.(jpe?g|png|webp|gif)$/i', $asset) === 1;
    }

    /**
     * Absolute URLs pass through untouched; local paths resolve on the public disk.
     */
    private function resolveMediaUrl(string $asset): string
    {
        return $this->isAbsoluteUrl($asset)
            ? $asset
            : Storage::disk('public')->url($asset);
    }

    private function isAbsoluteUrl(string $asset): bool
    {
        return str_starts_with($asset, 'http://') || str_starts_with($asset, 'https://');
    }

    protected function casts(): array
    {
        return [
            'media_assets' => 'array',
            'pricing_structure' => 'array',
            'promotional_content' => 'array',
            'cost' => 'decimal:2',
            'sales_commission' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'status' => ProductStatus::class,
        ];
    }
}
