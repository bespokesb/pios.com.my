<?php

namespace App\Filament\Resources\FeeSettings\Schemas;

use App\Enums\FeeType;
use App\Enums\RecordStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeeSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('fee_type')
                    ->options(FeeType::options())
                    ->default(FeeType::BusinessOwnerMonthly->value)
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('currency')
                    ->required()
                    ->default('MYR'),
                DatePicker::make('effective_from'),
                DatePicker::make('effective_until'),
                Select::make('status')
                    ->options(RecordStatus::options())
                    ->default(RecordStatus::Active->value)
                    ->required(),
            ]);
    }
}
