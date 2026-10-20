<?php

namespace App\Filament\Resources\CustomerTags\Pages;

use App\Filament\Resources\CustomerTags\CustomerTagResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerTag extends EditRecord
{
    protected static string $resource = CustomerTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
