<?php

namespace App\Filament\Resources\BusinessOwnerProductSelections\Pages;

use App\Filament\Resources\BusinessOwnerProductSelections\BusinessOwnerProductSelectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessOwnerProductSelections extends ListRecords
{
    protected static string $resource = BusinessOwnerProductSelectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
