<?php

namespace App\Filament\Resources\ProductTypes\Schemas;

use App\Enums\RecordStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                Select::make('status')
                    ->options(RecordStatus::options())
                    ->default(RecordStatus::Active->value)
                    ->required(),
            ]);
    }
}
