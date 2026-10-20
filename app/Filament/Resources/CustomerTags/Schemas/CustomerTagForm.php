<?php

namespace App\Filament\Resources\CustomerTags\Schemas;

use App\Enums\RecordStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerTagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('pios.customer.tags.label'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->options(RecordStatus::options())
                    ->default(RecordStatus::Active->value)
                    ->required(),
            ]);
    }
}
