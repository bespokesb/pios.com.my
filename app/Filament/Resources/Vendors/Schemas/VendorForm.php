<?php

namespace App\Filament\Resources\Vendors\Schemas;

use App\Enums\RecordStatus;
use App\Filament\Support\PhoneInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('pios.vendor.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('contact_name')
                    ->label(__('pios.vendor.contact_name'))
                    ->maxLength(255),
                PhoneInput::make('phone')
                    ->label(__('pios.vendor.phone')),
                TextInput::make('email')
                    ->label(__('pios.vendor.email'))
                    ->email()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label(__('pios.vendor.address'))
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('status')
                    ->label(__('pios.vendor.status'))
                    ->options(RecordStatus::options())
                    ->default(RecordStatus::Active->value)
                    ->required(),
            ]);
    }
}
