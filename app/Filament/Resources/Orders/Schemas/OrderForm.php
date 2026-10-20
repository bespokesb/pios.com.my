<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Customer;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Seller and Customer')
                    ->schema([
                        Select::make('business_owner_id')
                            ->label('Business Owner')
                            ->options(fn (): array => BusinessOwner::query()
                                ->orderBy('applicant_name')
                                ->get()
                                ->mapWithKeys(fn (BusinessOwner $businessOwner): array => [
                                    $businessOwner->id => $businessOwner->applicant_name.' - '.$businessOwner->business_owner_no,
                                ])
                                ->all())
                            ->default(fn (): ?int => auth()->user()?->businessOwner?->id)
                            ->disabled(fn (): bool => (bool) auth()->user()?->isBusinessOwner())
                            ->searchable()
                            ->required(),
                        Select::make('customer_id')
                            ->label('Customer')
                            ->options(fn (): array => Customer::query()
                                ->visibleTo(auth()->user())
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Customer $customer): array => [
                                    $customer->id => $customer->name.' - '.$customer->customer_no,
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Order Details')
                    ->schema([
                        Select::make('status')
                            ->options(OrderStatus::options())
                            ->default(OrderStatus::Draft->value)
                            ->required(),
                        DateTimePicker::make('ordered_at'),
                    ])
                    ->columns(2),

                Section::make('Items')
                    ->schema([
                        Repeater::make('items')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(fn (): array => Product::query()
                                        ->where('status', ProductStatus::Active->value)
                                        ->whereIn('id', BusinessOwnerProductSelection::query()
                                            ->where('status', '!=', ProductSelectionStatus::Inactive->value)
                                            ->pluck('product_id'))
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Product $product): array => [
                                            $product->id => $product->name.' - '.$product->product_no,
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->minItems(1)
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Totals')
                    ->schema([
                        TextInput::make('subtotal')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('RM'),
                        TextInput::make('total')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('RM'),
                    ])
                    ->columns(2),
            ]);
    }
}
