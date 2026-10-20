<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\CustomerCategory;
use App\Enums\RecordStatus;
use App\Filament\Support\AddressFields;
use App\Filament\Support\PhoneInput;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\CustomerTag;
use App\Models\Partner;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('pios.section.owner'))
                    ->schema([
                        Select::make('business_owner_id')
                            ->label(__('pios.field.business_owner'))
                            ->options(function (): array {
                                return BusinessOwner::query()
                                    ->with('partner')
                                    ->orderBy('applicant_name')
                                    ->get()
                                    ->mapWithKeys(fn (BusinessOwner $businessOwner): array => [
                                        $businessOwner->id => $businessOwner->applicant_name.' - '.$businessOwner->business_owner_no,
                                    ])
                                    ->all();
                            })
                            ->default(fn (): ?int => auth()->user()?->businessOwner?->id)
                            ->disabled(fn (): bool => (bool) auth()->user()?->actsAsBusinessOwner())
                            ->searchable()
                            ->required(),
                        Select::make('partner_id')
                            ->label(__('pios.field.parent_partner'))
                            ->options(fn (): array => Partner::query()
                                ->orderBy('applicant_name')
                                ->pluck('applicant_name', 'id')
                                ->all())
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    // Only HQ/admin assigns a customer to a Business Owner. A Partner
                    // creating a customer owns it directly (no BO), so the picker is hidden.
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->columns(2),

                Section::make(__('pios.section.contact'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('pios.field.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nick_name')
                            ->label(__('pios.field.nick_name'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->maxLength(255),
                        PhoneInput::make('hp_no')
                            ->label(__('pios.field.hp_no'))
                            ->required(fn (): bool => (bool) auth()->user()?->actsAsBusinessOwner()),
                        TextInput::make('kp_no')
                            ->label(__('pios.field.ic_no'))
                            ->visible(self::visibleToNonBusinessOwner())
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('pios.field.email'))
                            ->email()
                            ->maxLength(255),
                        Select::make('tags')
                            ->label(__('pios.customer.tags.label'))
                            ->placeholder(__('pios.customer.tags.placeholder'))
                            ->multiple()
                            ->searchable()
                            // Options come from the admin-managed tag list (DP-15);
                            // the record's current tags are merged in so editing
                            // never drops a tag that predates the vocabulary.
                            ->options(fn (?Customer $record): array => CustomerTag::options($record?->tags ?? []))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('pios.section.category'))
                    ->schema([
                        Select::make('category')
                            ->options(CustomerCategory::options())
                            ->default(CustomerCategory::Lead->value)
                            ->live()
                            ->required(),
                        Select::make('status')
                            ->options(RecordStatus::options())
                            ->default(RecordStatus::Active->value)
                            ->required(),
                        Toggle::make('is_vip')
                            ->label(__('pios.customer.vip.is_vip'))
                            ->helperText(__('pios.customer.vip.hint'))
                            ->live(),
                    ])
                    ->visible(self::visibleToNonBusinessOwner())
                    ->columns(2),

                // DP-8: optional profile fields, available to every seller
                // (including Business Owners) as a collapsed, non-intrusive section.
                Section::make(__('pios.customer.additional.heading'))
                    ->description(__('pios.customer.additional.hint'))
                    ->collapsed()
                    ->schema([
                        TextInput::make('age')
                            ->label(__('pios.customer.fields.age'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(150)
                            ->integer(),
                        Select::make('marital_status')
                            ->label(__('pios.customer.fields.marital_status'))
                            ->options([
                                'single' => __('pios.customer.marital.single'),
                                'married' => __('pios.customer.marital.married'),
                                'divorced' => __('pios.customer.marital.divorced'),
                                'widowed' => __('pios.customer.marital.widowed'),
                            ]),
                        TextInput::make('occupation')
                            ->label(__('pios.customer.fields.occupation'))
                            ->maxLength(255),
                        Textarea::make('remarks')
                            ->label(__('pios.customer.fields.remarks'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Standard PIOS address form — identical to the Business Owner page.
                Section::make(__('pios.address.section'))
                    ->schema(AddressFields::make())
                    ->columns(3),

                Section::make(__('pios.section.los_details'))
                    ->schema([
                        TextInput::make('abo_no')
                            ->label(__('pios.field.abo_no'))
                            ->required(fn (Get $get): bool => (bool) $get('is_vip'))
                            ->maxLength(255),
                        TextInput::make('dd_name')
                            ->label(__('pios.field.dd_name'))
                            ->maxLength(255),
                        PhoneInput::make('dd_hp_no')
                            ->label(__('pios.field.dd_hp_no')),
                    ])
                    ->visible(fn (Get $get): bool => ! (auth()->user()?->actsAsBusinessOwner() ?? false)
                        && (bool) $get('is_vip'))
                    ->columns(3),
            ]);
    }

    /**
     * Business Owners get a minimal customer form (name, phone, optional email);
     * the richer fields are reserved for HQ/admin users.
     */
    private static function visibleToNonBusinessOwner(): Closure
    {
        return fn (): bool => ! (auth()->user()?->actsAsBusinessOwner() ?? false);
    }
}
