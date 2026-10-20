<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\IdentifierGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['product_no']);

        $user = auth()->user();

        if ($user?->isBusinessOwner()) {
            if (! $user->businessOwner) {
                throw ValidationException::withMessages([
                    'data.name' => 'Your user account is not linked to a Business Owner profile.',
                ]);
            }

            if ($user->businessOwner->hasReachedProductLimit()) {
                throw ValidationException::withMessages([
                    'data.name' => 'You have reached the maximum limit of '.Product::MAX_PER_BUSINESS_OWNER.' products per Business Owner.',
                ]);
            }

            // A Business Owner (including a Partner selling through their own BO
            // profile) only ever creates products they own.
            $data['business_owner_id'] = $user->businessOwner->id;

            // Defense-in-depth: commercial-confidential fields are never BO-writable.
            foreach (['cost', 'vendor_id', 'sales_commission', 'territory_id', 'pricing_structure', 'promotional_content', 'coverage_area', 'certification'] as $adminOnly) {
                unset($data[$adminOnly]);
            }
        }

        $data['product_no'] = app(IdentifierGenerator::class)->generate(
            'product',
            Product::class,
            'product_no',
        );
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return Product::query()->create($data);
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
}
