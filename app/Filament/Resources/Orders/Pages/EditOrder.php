<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Customer;
use App\Models\Product;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    private array $oldValues = [];

    private array $orderItems = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items()
            ->get(['product_id', 'quantity'])
            ->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->fresh(['items'])->toArray();
        $submittedItems = $data['items'] ?? [];

        unset($data['items'], $data['order_no'], $data['partner_id'], $data['subtotal'], $data['total']);

        $businessOwner = $this->resolveBusinessOwner($data);
        $customer = $this->resolveCustomer($data, $businessOwner);
        [$items, $subtotal] = $this->buildOrderItems($submittedItems, $businessOwner, $customer);

        $this->orderItems = $items;

        $data['business_owner_id'] = $businessOwner->id;
        $data['partner_id'] = $businessOwner->partner_id;
        $data['customer_id'] = $customer->id;
        $data['subtotal'] = $subtotal;
        $data['total'] = $subtotal;

        if (($data['status'] ?? OrderStatus::Draft->value) !== OrderStatus::Draft->value && blank($data['ordered_at'] ?? null)) {
            $data['ordered_at'] = now();
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);
        $record->items()->delete();
        $record->items()->createMany($this->orderItems);

        return $record;
    }

    protected function afterSave(): void
    {
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'updated',
            auditable: $this->record,
            oldValues: $this->oldValues,
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
