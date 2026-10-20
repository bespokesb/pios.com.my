<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Services\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

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
