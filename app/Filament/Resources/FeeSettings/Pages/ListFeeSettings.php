<?php

namespace App\Filament\Resources\FeeSettings\Pages;

use App\Filament\Resources\FeeSettings\FeeSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeeSettings extends ListRecords
{
    protected static string $resource = FeeSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
