<?php

namespace App\Filament\Resources\Partners\Schemas;

use App\Enums\RecordStatus;
use App\Filament\Support\PhoneInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Applicant')
                    ->schema([
                        TextInput::make('applicant_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('ic_no')
                            ->label('IC No')
                            ->maxLength(255),
                        PhoneInput::make('hp_no')
                            ->label('HP No'),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Spouse')
                    ->schema([
                        TextInput::make('spouse_name')
                            ->maxLength(255),
                        TextInput::make('spouse_ic_no')
                            ->label('Spouse IC No')
                            ->maxLength(255),
                        PhoneInput::make('spouse_hp_no')
                            ->label('Spouse HP No'),
                        TextInput::make('spouse_email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Contact and Address')
                    ->schema([
                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('bandar')
                            ->maxLength(255),
                        TextInput::make('negeri')
                            ->maxLength(255),
                        TextInput::make('poskod')
                            ->maxLength(16),
                    ])
                    ->columns(3),

                Section::make('Partner Details')
                    ->schema([
                        Select::make('parent_partner_id')
                            ->relationship('parentPartner', 'applicant_name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => ! (auth()->user()?->actsAsPartner() ?? false)),
                        // Territory hidden for now — not relevant to the current flow.
                        Select::make('partner_tier_id')
                            ->relationship('partnerTier', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('abo_no')
                            ->label('ABO No')
                            ->maxLength(255),
                        TextInput::make('diamond_name')
                            ->maxLength(255),
                        Select::make('status')
                            ->options(RecordStatus::options())
                            ->default(RecordStatus::Active->value)
                            ->visible(fn (): bool => ! (auth()->user()?->actsAsPartner() ?? false))
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Account Access')
                    ->schema([
                        Toggle::make('create_user')
                            ->label('Create login account')
                            ->helperText('An activation email is sent to the partner to set their own password — no password is set here.')
                            ->default(false)
                            ->dehydrated(),
                    ]),
            ]);
    }
}
