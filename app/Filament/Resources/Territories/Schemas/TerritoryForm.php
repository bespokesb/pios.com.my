<?php

namespace App\Filament\Resources\Territories\Schemas;

use App\Enums\RecordStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TerritoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('coverage_area'),
                Select::make('status')
                    ->options(RecordStatus::options())
                    ->default(RecordStatus::Active->value)
                    ->required(),
            ]);
    }
}
