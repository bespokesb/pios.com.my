<?php

namespace App\Filament\Resources\BusinessOwners\Pages;

use App\Filament\Resources\BusinessOwners\BusinessOwnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessOwners extends ListRecords
{
    protected static string $resource = BusinessOwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
