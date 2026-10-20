<?php

namespace App\Filament\Resources\BusinessOwners\Schemas;

use App\Enums\RecordStatus;
use App\Filament\Support\AddressFields;
use App\Filament\Support\PhoneInput;
use App\Models\Partner;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BusinessOwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('pios.section.parent_partner'))
                    ->schema([
                        Select::make('partner_id')
                            ->label(__('pios.field.parent_partner'))
                            ->options(function (): array {
                                $user = auth()->user();

                                if (! $user) {
                                    return [];
                                }

                                return Partner::query()
                                    ->orderBy('applicant_name')
                                    ->pluck('applicant_name', 'id')
                                    ->all();
                            })
                            ->default(fn (): ?int => auth()->user()?->partner?->id)
                            ->disabled(fn (): bool => (bool) auth()->user()?->actsAsPartner())
                            ->searchable()
                            ->required(),
                        // Territory hidden for now — not relevant to the current flow.
                    ])
                    ->columns(2),

                Section::make(__('pios.section.contact'))
                    ->schema([
                        TextInput::make('applicant_name')
                            ->label(__('pios.field.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('ic_no')
                            ->label(__('pios.field.ic_no'))
                            ->maxLength(255),
                        PhoneInput::make('hp_no')
                            ->label(__('pios.field.hp_no')),
                        TextInput::make('email')
                            ->label(__('pios.field.email'))
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('pios.section.spouse'))
                    ->schema([
                        TextInput::make('spouse_name')
                            ->label(__('pios.field.spouse_name'))
                            ->maxLength(255),
                        TextInput::make('spouse_ic_no')
                            ->label(__('pios.field.spouse_ic_no'))
                            ->maxLength(255),
                        PhoneInput::make('spouse_hp_no')
                            ->label(__('pios.field.spouse_hp_no')),
                        TextInput::make('spouse_email')
                            ->label(__('pios.field.spouse_email'))
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('pios.address.section'))
                    ->schema(AddressFields::make())
                    ->columns(3),

                Section::make(__('pios.section.business_details'))
                    ->schema([
                        TextInput::make('company_name')
                            ->label(__('pios.field.business_name'))
                            ->maxLength(255),
                        Textarea::make('product_description')
                            ->label(__('pios.field.business_description'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options(RecordStatus::options())
                            ->default(RecordStatus::Active->value)
                            ->visible(fn (): bool => ! (auth()->user()?->actsAsPartner() ?? false))
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('pios.blaster.title'))
                    ->description(__('pios.blaster.section_hint'))
                    ->schema([
                        TextInput::make('ustazai_api_key')
                            ->label(__('pios.blaster.api_key'))
                            ->helperText(__('pios.blaster.api_key_hint'))
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                        TextInput::make('ustazai_sender')
                            ->label(__('pios.blaster.sender'))
                            ->helperText(__('pios.blaster.sender_hint'))
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('pios.section.account_access'))
                    ->schema([
                        Toggle::make('create_user')
                            ->label(__('pios.field.create_login'))
                            ->helperText(__('pios.field.create_login_hint'))
                            ->default(false)
                            ->live()
                            ->dehydrated(),
                        TextInput::make('account_password')
                            ->label(__('pios.field.password'))
                            ->helperText(__('pios.field.password_hint'))
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => (bool) $get('create_user'))
                            // Not a model column — captured in the create page and
                            // unset before the record is saved (like create_user).
                            ->dehydrated(),
                    ]),
            ]);
    }
}
