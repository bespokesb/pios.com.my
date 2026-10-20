<?php

namespace App\Filament\Resources\FeeSettings\Pages;

use App\Filament\Resources\FeeSettings\FeeSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeeSetting extends EditRecord
{
    protected static string $resource = FeeSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
