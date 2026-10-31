<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_no',
        'receipt_no',
        'subscription_id',
        'subscription_product_id',
        'user_id',
        'payer_role',
        'partner_id',
        'business_owner_id',
        'amount',
        'period_days',
        'payment_method',
        'payment_proof',
        'reference_no',
        'notes',
        'paid_at',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_days' => 'integer',
            'paid_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paymentProofUrl(): ?string
    {
        if (! filled($this->payment_proof)) {
            return null;
        }

        if (str_starts_with($this->payment_proof, 'http://') || str_starts_with($this->payment_proof, 'https://')) {
            return $this->payment_proof;
        }

        return Storage::disk('public')->url($this->payment_proof);
    }

    public function payerName(): string
    {
        if ($this->businessOwner) {
            return $this->businessOwner->applicant_name ?: ($this->user?->name ?? 'Business Owner');
        }

        if ($this->partner) {
            return $this->partner->applicant_name ?: ($this->user?->name ?? 'Partner');
        }

        return $this->user?->name ?? '-';
    }

    public function payerHpNo(): ?string
    {
        if ($this->businessOwner) {
            return $this->businessOwner->hp_no;
        }

        if ($this->partner) {
            return $this->partner->hp_no;
        }

        return null;
    }

    public function payerIdentifier(): string
    {
        if ($this->businessOwner) {
            return $this->businessOwner->business_owner_no ?: ('BO#'.$this->businessOwner->id);
        }

        if ($this->partner) {
            return $this->partner->partner_no ?: ('P#'.$this->partner->id);
        }

        return '-';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve this payment and activate/extend the underlying subscription.
     */
    public function approve(User $approver): void
    {
        DB::transaction(function () use ($approver): void {
            $days = $this->period_days ?: ($this->product?->period_days ?: 30);

            // Generate receipt number if not yet generated
            if (empty($this->receipt_no)) {
                $seq = IdentifierSequence::firstOrCreate(
                    ['key' => 'receipt_no'],
                    ['prefix' => 'RCT'.date('ym'), 'next_number' => 1, 'padding' => 5]
                );
                $this->receipt_no = $seq->prefix.str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
                $seq->increment('next_number');
            }

            $this->status = 'approved';
            $this->approved_by = $approver->id;
            $this->approved_at = now();
            $this->save();

            // Update Subscription
            $sub = $this->subscription;
            if ($sub) {
                if ($sub->starts_at === null) {
                    $sub->starts_at = now();
                }

                if ($sub->isActive() && $sub->expires_at && $sub->expires_at->isFuture()) {
                    $sub->expires_at = $sub->expires_at->copy()->addDays($days);
                } else {
                    $sub->expires_at = now()->addDays($days);
                }

                $sub->status = 'active';
                $sub->last_renewed_at = now();
                $sub->save();
            }
        });
    }

    /**
     * Reject this payment with an optional reason.
     */
    public function reject(User $approver, ?string $reason = null): void
    {
        $this->status = 'rejected';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        $this->save();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
