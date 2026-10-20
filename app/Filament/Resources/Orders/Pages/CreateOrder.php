<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\IdentifierGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    private array $orderItems = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $submittedItems = $data['items'] ?? [];

        unset($data['items'], $data['order_no'], $data['partner_id'], $data['subtotal'], $data['total']);

        $businessOwner = $this->resolveBusinessOwner($data);
        $customer = $this->resolveCustomer($data, $businessOwner);
        [$items, $subtotal] = $this->buildOrderItems($submittedItems, $businessOwner, $customer);

        $this->orderItems = $items;

        $data['business_owner_id'] = $businessOwner->id;
        $data['partner_id'] = $businessOwner->partner_id;
        $data['customer_id'] = $customer->id;
        $data['order_no'] = app(IdentifierGenerator::class)->generate('order', Order::class, 'order_no');
        $data['subtotal'] = $subtotal;
        $data['total'] = $subtotal;

        if (($data['status'] ?? OrderStatus::Draft->value) !== OrderStatus::Draft->value && blank($data['ordered_at'] ?? null)) {
            $data['ordered_at'] = now();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return Order::query()->create($data);
    }

    protected function afterCreate(): void
    {
        $this->record->items()->createMany($this->orderItems);

        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'created',
            auditable: $this->record,
            newValues: $this->record->fresh(['items'])->toArray(),
        );
    }

    private function resolveBusinessOwner(array $data): BusinessOwner
    {
        $user = auth()->user();

        if ($user?->isBusinessOwner()) {
            if (! $user->businessOwner) {
                throw ValidationException::withMessages([
                    'data.business_owner_id' => 'Your user account is not linked to a Business Owner profile.',
                ]);
            }

            return $user->businessOwner;
        }

        $businessOwner = BusinessOwner::query()->find($data['business_owner_id'] ?? null);

        if (! $businessOwner) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner is required.',
            ]);
        }

        return $businessOwner;
    }

    private function resolveCustomer(array $data, BusinessOwner $businessOwner): Customer
    {
        $customer = Customer::query()->find($data['customer_id'] ?? null);

        if (! $customer || $customer->business_owner_id !== $businessOwner->id) {
            throw ValidationException::withMessages([
                'data.customer_id' => 'Customer must belong to the selected Business Owner.',
            ]);
        }

        return $customer;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    private function buildOrderItems(array $submittedItems, BusinessOwner $businessOwner, Customer $customer): array
    {
        if ($submittedItems === []) {
            throw ValidationException::withMessages([
                'data.items' => 'At least one order item is required.',
            ]);
        }

        $items = [];
        $subtotal = 0.0;

        foreach (array_values($submittedItems) as $index => $submittedItem) {
            $product = Product::query()->find($submittedItem['product_id'] ?? null);
            $quantity = max(1, (int) ($submittedItem['quantity'] ?? 1));

            if (! $product || $product->status !== ProductStatus::Active) {
                throw ValidationException::withMessages([
                    "data.items.{$index}.product_id" => 'Only active products can be ordered.',
                ]);
            }

            if (! BusinessOwnerProductSelection::query()
                ->where('business_owner_id', $businessOwner->id)
                ->where('product_id', $product->id)
                ->where('status', '!=', ProductSelectionStatus::Inactive->value)
                ->exists()) {
                throw ValidationException::withMessages([
                    "data.items.{$index}.product_id" => 'Product is not selected for this Business Owner.',
                ]);
            }

            if (! $customer->canBuyProduct($product)) {
                throw ValidationException::withMessages([
                    "data.items.{$index}.product_id" => 'Customer is not eligible to buy this product.',
                ]);
            }

            $unitPrice = (float) $product->selling_price;
            $lineTotal = round($unitPrice * $quantity, 2);
            $subtotal = round($subtotal + $lineTotal, 2);

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        return [$items, $subtotal];
    }
}
