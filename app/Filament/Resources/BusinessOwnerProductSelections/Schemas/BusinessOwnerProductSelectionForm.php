<?php

namespace App\Filament\Resources\BusinessOwnerProductSelections\Schemas;

use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Models\BusinessOwner;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BusinessOwnerProductSelectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Business Owner')
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
                    ]),

                Section::make('Selected Product')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn (): array => Product::query()
                                ->where('status', ProductStatus::Active->value)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Product $product): array => [
                                    $product->id => $product->name.' - '.$product->product_no,
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                        Select::make('status')
                            ->options(ProductSelectionStatus::options())
                            ->default(ProductSelectionStatus::Selected->value)
                            ->required(),
                        DateTimePicker::make('selected_at')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }
}
