<?php

namespace App\Services;

use App\Enums\CustomerCategory;
use App\Enums\OrderStatus;
use App\Enums\RecordStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderPlaced;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;

/**
 * Places a manual (no payment gateway) order coming from the public product
 * page. The order is attributed to the product's owning Business Owner, a Lead
 * customer is matched-or-created under that BO, and the order lands as
 * Submitted for the BO to fulfil by hand.
 */
class ManualOrderService
{
    public function __construct(
        private readonly IdentifierGenerator $identifiers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{name: string, phone: string, quantity: int|string, address?: ?string, note?: ?string}  $buyer
     */
    public function placePublicOrder(Product $product, array $buyer): Order
    {
        $order = DB::transaction(function () use ($product, $buyer): Order {
            $businessOwner = $product->businessOwner;
            $customer = $this->matchOrCreateLead($product, $buyer);

            $quantity = max(1, (int) $buyer['quantity']);
            $unitPrice = (float) $product->selling_price;
            $lineTotal = round($unitPrice * $quantity, 2);

            $order = Order::create([
                'business_owner_id' => $businessOwner->id,
                'partner_id' => $businessOwner->partner_id,
                'customer_id' => $customer->id,
                'order_no' => $this->identifiers->generate('order', Order::class, 'order_no'),
                'status' => OrderStatus::Submitted,
                'subtotal' => $lineTotal,
                'total' => $lineTotal,
                'ordered_at' => now(),
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $this->audit->log(
                actor: User::system(),
                event: 'public_order_placed',
                auditable: $order,
                newValues: $order->getAttributes(),
            );

            return $order;
        });

        // Alert the owning BO AFTER commit (in-panel bell + queued email); a
        // notification failure must never roll back or block the public order.
        $product->businessOwner->user?->notify(new OrderPlaced($order->load('customer')));

        return $order;
    }

    /**
     * Find an existing customer of this BO by normalized phone, or create a Lead.
     * Scoped strictly to the product's BO so ownership is never crossed.
     */
    private function matchOrCreateLead(Product $product, array $buyer): Customer
    {
        $businessOwner = $product->businessOwner;
        // Canonical wa.me form (60…), matching the PhoneNumber cast + saving hook.
        $key = Phone::toWhatsApp($buyer['phone']);

        if ($key !== null) {
            // Indexed equality match on (business_owner_id, hp_no_normalized) —
            // bounded, no full-table load, safe for the public endpoint.
            $existing = Customer::query()
                ->where('business_owner_id', $businessOwner->id)
                ->where('hp_no_normalized', $key)
                ->first();

            if ($existing) {
                $addressData = array_filter([
                    'address' => $buyer['address'] ?? null,
                    'address_line_2' => $buyer['address_line_2'] ?? null,
                    'address_line_3' => $buyer['address_line_3'] ?? null,
                    'bandar' => $buyer['bandar'] ?? null,
                    'negeri' => $buyer['negeri'] ?? null,
                    'poskod' => $buyer['poskod'] ?? null,
                ], fn ($val) => filled($val));

                if (! empty($addressData)) {
                    $existing->update($addressData);
                }

                return $existing;
            }
        }

        return Customer::create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $businessOwner->partner_id,
            'customer_no' => $this->identifiers->generate('customer', Customer::class, 'customer_no'),
            'category' => CustomerCategory::Lead,
            'name' => $buyer['name'],
            'hp_no' => $buyer['phone'],
            'address' => $buyer['address'] ?? null,
            'address_line_2' => $buyer['address_line_2'] ?? null,
            'address_line_3' => $buyer['address_line_3'] ?? null,
            'bandar' => $buyer['bandar'] ?? null,
            'negeri' => $buyer['negeri'] ?? null,
            'poskod' => $buyer['poskod'] ?? null,
            'status' => RecordStatus::Active,
        ]);
    }
}
