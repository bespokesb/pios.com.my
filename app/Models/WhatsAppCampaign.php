<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_owner_id',
    'product_id',
    'title',
    'message',
    'media_url',
    'target_type',
    'target_customer_ids',
    'custom_phones',
    'status',
    'scheduled_at',
    'sent_at',
    'total_recipients',
    'sent_count',
    'failed_count',
    'result_summary',
    'created_by',
])]
class WhatsAppCampaign extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_campaigns';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'target_customer_ids' => 'array',
            'result_summary' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Resolve target recipients for this campaign.
     *
     * @return array<int, array{phone: string, name: string}>
     */
    public function resolveTargets(): array
    {
        $targets = [];

        if ($this->target_type === 'custom') {
            $lines = preg_split('/[\r\n,;]+/', (string) $this->custom_phones) ?: [];
            foreach ($lines as $line) {
                $phone = trim($line);
                if ($phone !== '') {
                    $targets[] = ['phone' => $phone, 'name' => 'Pelanggan'];
                }
            }

            return $targets;
        }

        $query = Customer::query()->where('business_owner_id', $this->business_owner_id);

        if ($this->target_type === 'selected') {
            $ids = array_filter(array_map('intval', (array) $this->target_customer_ids));
            if (empty($ids)) {
                return [];
            }
            $query->whereIn('id', $ids);
        } elseif ($this->target_type === 'vip') {
            $query->where('is_vip', true);
        } elseif ($this->target_type === 'non_vip') {
            $query->where(function ($q): void {
                $q->where('is_vip', false)->orWhereNull('is_vip');
            });
        }

        $customers = $query->get();
        foreach ($customers as $customer) {
            if (! empty($customer->hp_no)) {
                $targets[] = [
                    'phone' => (string) $customer->hp_no,
                    'name' => (string) ($customer->name ?: 'Pelanggan'),
                ];
            }
        }

        return $targets;
    }

    /**
     * Render campaign message replacing product placeholders.
     */
    public function renderMessageTemplate(): string
    {
        $text = (string) $this->message;

        if ($this->product) {
            $text = str_replace(
                ['{product_name}', '{produk}', '{price}', '{harga}', '{link}', '{pautan}'],
                [
                    (string) $this->product->name,
                    (string) $this->product->name,
                    'RM '.number_format((float) $this->product->selling_price, 2),
                    'RM '.number_format((float) $this->product->selling_price, 2),
                    $this->product->publicUrl(),
                    $this->product->publicUrl(),
                ],
                $text
            );
        }

        return $text;
    }

    public function resolveMediaUrl(): ?string
    {
        if (! empty($this->media_url)) {
            return $this->media_url;
        }

        return $this->product?->firstShareableImageUrl();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draf',
            self::STATUS_SCHEDULED => 'Dijadualkan',
            self::STATUS_PROCESSING => 'Sedang Dihantar',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_PARTIAL => 'Sebahagian Berjaya',
            self::STATUS_FAILED => 'Gagal',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    public function targetTypeLabel(): string
    {
        return match ($this->target_type) {
            'all' => 'Semua Pelanggan',
            'vip' => 'Pelanggan VIP (LOS) Sahaja',
            'non_vip' => 'Pelanggan Biasa (Non-VIP)',
            'selected' => 'Pelanggan Terpilih',
            'custom' => 'Nombor Khas',
            default => ucfirst((string) $this->target_type),
        };
    }
}
