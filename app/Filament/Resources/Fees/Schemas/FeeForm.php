<?php

namespace App\Filament\Resources\Fees\Schemas;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Scope')
                    ->schema([
                        Select::make('partner_id')
                            ->label('Partner')
                            ->options(fn (): array => Partner::query()->orderBy('applicant_name')->pluck('applicant_name', 'id')->all())
                            ->searchable(),
                        Select::make('business_owner_id')
                            ->label('Business Owner')
                            ->options(fn (): array => BusinessOwner::query()
                                ->orderBy('applicant_name')
                                ->get()
                                ->mapWithKeys(fn (BusinessOwner $businessOwner): array => [
                                    $businessOwner->id => $businessOwner->applicant_name.' - '.$businessOwner->business_owner_no,
                                ])
                                ->all())
                            ->searchable(),
                        Select::make('customer_id')
                            ->label('Customer')
                            ->options(fn (): array => Customer::query()
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Customer $customer): array => [
                                    $customer->id => $customer->name.' - '.$customer->customer_no,
                                ])
                                ->all())
                            ->searchable(),
                    ])
                    ->columns(3),

                Section::make('Fee')
                    ->schema([
                        Select::make('fee_type')
                            ->options(FeeType::options())
                            ->default(FeeType::BusinessOwnerMonthly->value)
                            ->required(),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('RM'),
                        DatePicker::make('due_date'),
                        Select::make('status')
                            ->options(FeeStatus::options())
                            ->default(FeeStatus::Unpaid->value)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
