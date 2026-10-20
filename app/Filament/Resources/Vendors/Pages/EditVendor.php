<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

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

        return $data;
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
