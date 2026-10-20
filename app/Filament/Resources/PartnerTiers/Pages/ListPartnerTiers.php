<?php

namespace App\Filament\Resources\PartnerTiers\Pages;

use App\Filament\Resources\PartnerTiers\PartnerTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartnerTiers extends ListRecords
{
    protected static string $resource = PartnerTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
