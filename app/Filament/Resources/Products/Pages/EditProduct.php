<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    private array $oldValues = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->getOriginal();

        unset($data['product_no']);

        // The owner FK and creator are immutable post-create. Strip them for
        // EVERY role so a crafted payload can never reassign a product to another
        // Business Owner (a cross-BO write the read-scope cannot stop) or rewrite
        // the audit trail of who created it.
        unset($data['business_owner_id'], $data['created_by']);

        // A Business Owner can never overwrite the admin-only commercial fields.
        if (auth()->user()?->isBusinessOwner()) {
            foreach (['cost', 'vendor_id', 'sales_commission', 'territory_id', 'pricing_structure', 'promotional_content', 'coverage_area', 'certification'] as $adminOnly) {
                unset($data[$adminOnly]);
            }
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
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'updated',
            auditable: $this->record,
            oldValues: $this->oldValues,
            newValues: $this->record->fresh()->getAttributes(),
        );
    }
}
