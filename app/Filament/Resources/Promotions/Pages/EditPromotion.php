<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Enums\PromotionMode;
use App\Enums\PromotionStatus;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Customer;
use App\Models\Product;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;

    /** @var array<int, int> */
    private array $customerIds = [];

    private array $oldValues = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['customer_ids'] = $this->record->customers()->pluck('customers.id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->getOriginal();
        $this->customerIds = array_values(array_map('intval', $data['customer_ids'] ?? []));

        unset($data['customer_ids'], $data['status'], $data['ran_at'], $data['partner_id'], $data['created_by']);

        $businessOwner = $this->resolveBusinessOwner($data);
        $product = $this->resolveProduct($data, $businessOwner);
        $customers = $this->resolveCustomers($businessOwner, $product, $this->customerIds);
        $this->validateTiming($data);
        $this->validateDiscount($data);
        $this->validateCustomerLimit($data, $customers);

        $mode = $data['mode'] ?? PromotionMode::Manual->value;

        $data['business_owner_id'] = $businessOwner->id;
        $data['partner_id'] = $businessOwner->partner_id;
        $data['product_id'] = $product->id;

        if (! in_array($this->record->status, [PromotionStatus::Completed, PromotionStatus::Cancelled], true)) {
            $data['status'] = $mode === PromotionMode::Auto->value
                ? PromotionStatus::Scheduled->value
                : PromotionStatus::Running->value;
            $data['ran_at'] = $mode === PromotionMode::Auto->value ? null : ($this->record->ran_at ?: now());
            $data['starts_at'] ??= $mode === PromotionMode::Manual->value ? now() : null;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        $this->syncCustomers();

        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'updated',
            auditable: $this->record,
            oldValues: $this->oldValues,
            newValues: $this->record->fresh(['customers'])->toArray(),
        );
    }

    private function syncCustomers(): void
    {
        $pivot = collect($this->customerIds)
            ->mapWithKeys(fn (int $customerId): array => [
                $customerId => [
                    'status' => $this->record->status === PromotionStatus::Running ? 'sent' : 'selected',
                    'sent_at' => $this->record->status === PromotionStatus::Running ? ($this->record->ran_at ?: now()) : null,
                ],
            ])
            ->all();

        $this->record->customers()->sync($pivot);
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

        $businessOwner = BusinessOwner::query()->visibleTo($user)->find($data['business_owner_id'] ?? null);

        if (! $businessOwner) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner is required.',
            ]);
        }

        return $businessOwner;
    }

    private function resolveProduct(array $data, BusinessOwner $businessOwner): Product
    {
        $product = Product::query()->find($data['product_id'] ?? null);

        if (! $product || $product->status !== ProductStatus::Active) {
            throw ValidationException::withMessages([
                'data.product_id' => 'Only active products can be promoted.',
            ]);
        }

        if (! BusinessOwnerProductSelection::query()
            ->where('business_owner_id', $businessOwner->id)
            ->where('product_id', $product->id)
            ->where('status', '!=', ProductSelectionStatus::Inactive->value)
            ->exists()) {
            throw ValidationException::withMessages([
                'data.product_id' => 'Product must be selected by this Business Owner before promotion.',
            ]);
        }

        return $product;
    }

    /**
     * @param  array<int, int>  $customerIds
     * @return Collection<int, Customer>
     */
    private function resolveCustomers(BusinessOwner $businessOwner, Product $product, array $customerIds): Collection
    {
        if ($customerIds === []) {
            throw ValidationException::withMessages([
                'data.customer_ids' => 'Select at least one customer.',
            ]);
        }

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->where('business_owner_id', $businessOwner->id)
            ->get();

        if ($customers->count() !== count(array_unique($customerIds))) {
            throw ValidationException::withMessages([
                'data.customer_ids' => 'Selected customers must belong to this Business Owner.',
            ]);
        }

        $ineligibleCustomer = $customers->first(fn (Customer $customer): bool => ! $customer->canBuyProduct($product));

        if ($ineligibleCustomer) {
            throw ValidationException::withMessages([
                'data.customer_ids' => "{$ineligibleCustomer->name} is not eligible for the selected product.",
            ]);
        }

        return $customers;
    }

    private function validateTiming(array $data): void
    {
        if (($data['mode'] ?? PromotionMode::Manual->value) === PromotionMode::Auto->value
            && (blank($data['starts_at'] ?? null) || blank($data['ends_at'] ?? null))) {
            throw ValidationException::withMessages([
                'data.starts_at' => 'Auto promotions require a start time.',
                'data.ends_at' => 'Auto promotions require an end time.',
            ]);
        }
    }

    private function validateDiscount(array $data): void
    {
        if (($data['discount_type'] ?? 'amount') === 'percent'
            && filled($data['discount_value'] ?? null)
            && (float) $data['discount_value'] > 100) {
            throw ValidationException::withMessages([
                'data.discount_value' => 'Percent discount cannot exceed 100.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Customer>  $customers
     */
    private function validateCustomerLimit(array $data, Collection $customers): void
    {
        if (filled($data['max_customers'] ?? null) && $customers->count() > (int) $data['max_customers']) {
            throw ValidationException::withMessages([
                'data.customer_ids' => 'Selected customers exceed the max customer limit.',
            ]);
        }
    }
}
