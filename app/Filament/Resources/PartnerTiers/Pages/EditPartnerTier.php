<?php

namespace App\Filament\Resources\PartnerTiers\Pages;

use App\Filament\Resources\PartnerTiers\PartnerTierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnerTier extends EditRecord
{
    protected static string $resource = PartnerTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
