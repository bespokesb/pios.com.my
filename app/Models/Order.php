<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\ScopesByOwnerVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

#[Fillable(['business_owner_id', 'partner_id', 'customer_id', 'order_no', 'status', 'subtotal', 'total', 'tracking_number', 'ordered_at', 'paid_at', 'shipped_at'])]
class Order extends Model
{
    use ScopesByOwnerVisibility;

    /**
     * Move the order one legal step forward through its lifecycle (or to
     * Cancelled), stamping the relevant timestamp. Rejects illegal jumps — the
     * single server-side enforcement point for the status workflow (ORD-01).
     *
     * @throws InvalidArgumentException on an illegal transition.
     */
    public function transitionTo(OrderStatus $to, ?string $trackingNumber = null): void
    {
        if (! $this->status->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                "Cannot move order {$this->order_no} from {$this->status->value} to {$to->value}.",
            );
        }

        $this->status = $to;

        // An order that reaches Submitted must have an ordered_at, or it drops out
        // of every whereBetween('ordered_at', …) revenue window (dashboard/report/
        // chart) while still counting in the un-windowed total. ??= never
        // overwrites a value already set by ManualOrderService/EditOrder.
        if ($to === OrderStatus::Submitted) {
            $this->ordered_at ??= now();
        }

        if ($to === OrderStatus::Paid && $this->paid_at === null) {
            $this->paid_at = now();
        }

        if ($to === OrderStatus::Shipped) {
            if (filled($trackingNumber)) {
                $this->tracking_number = $trackingNumber;
            }

            // A shipment must carry a tracking number — enforced here so this
            // stays the single server-side authority even for a direct caller.
            if (blank($this->tracking_number)) {
                throw new InvalidArgumentException(
                    "Shipping order {$this->order_no} requires a tracking number.",
                );
            }

            $this->shipped_at = now();
        }

        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null || in_array($this->status, [
            OrderStatus::Paid,
            OrderStatus::Packed,
            OrderStatus::Shipped,
            OrderStatus::Completed,
        ]);
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(BusinessOwner::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
}
