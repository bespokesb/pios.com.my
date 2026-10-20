<?php

namespace App\Filament\Resources\BusinessOwnerProductSelections\Pages;

use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Filament\Resources\BusinessOwnerProductSelections\BusinessOwnerProductSelectionResource;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Product;
use App\Services\AuditLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateBusinessOwnerProductSelection extends CreateRecord
{
    protected static string $resource = BusinessOwnerProductSelectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['selected_at']);

        $data['business_owner_id'] = $this->resolveBusinessOwnerId($data);
        $this->validateSelection($data);

        if (($data['status'] ?? ProductSelectionStatus::Selected->value) !== ProductSelectionStatus::Inactive->value) {
            $data['selected_at'] = now();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return BusinessOwnerProductSelection::query()->create($data);
    }

    protected function afterCreate(): void
    {
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'created',
            auditable: $this->record,
            newValues: $this->record->fresh()->getAttributes(),
        );
    }

    private function resolveBusinessOwnerId(array $data): int
    {
        $user = auth()->user();

        if ($user?->isBusinessOwner()) {
            if (! $user->businessOwner) {
                throw ValidationException::withMessages([
                    'data.business_owner_id' => 'Your user account is not linked to a Business Owner profile.',
                ]);
            }

            return $user->businessOwner->id;
        }

        if (blank($data['business_owner_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner is required.',
            ]);
        }

        if (! BusinessOwner::query()->whereKey($data['business_owner_id'])->exists()) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner was not found.',
            ]);
        }

        return (int) $data['business_owner_id'];
    }

    private function validateSelection(array $data): void
    {
        $product = Product::query()->find($data['product_id'] ?? null);

        if (! $product || $product->status !== ProductStatus::Active) {
            throw ValidationException::withMessages([
                'data.product_id' => 'Only active catalog products can be selected.',
            ]);
        }

        if (BusinessOwnerProductSelection::query()
            ->where('business_owner_id', $data['business_owner_id'])
            ->where('product_id', $data['product_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'data.product_id' => 'This product is already selected for the Business Owner.',
            ]);
        }

        if (($data['status'] ?? null) === ProductSelectionStatus::Inactive->value) {
            return;
        }

        if (BusinessOwnerProductSelection::query()
            ->where('business_owner_id', $data['business_owner_id'])
            ->where('status', '!=', ProductSelectionStatus::Inactive->value)
            ->count() >= 5) {
            throw ValidationException::withMessages([
                'data.product_id' => 'A Business Owner can select a maximum of five active products.',
            ]);
        }
    }
}
