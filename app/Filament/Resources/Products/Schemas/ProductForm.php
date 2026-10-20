<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('pios.product.section.category'))
                    ->schema([
                        Select::make('product_type_id')
                            ->label(__('pios.product.category'))
                            ->relationship('productType', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('business_owner_id')
                            ->label(__('pios.nav.group.business_owner'))
                            ->relationship('businessOwner', 'applicant_name')
                            ->searchable()
                            ->preload()
                            ->visible(self::visibleToAdmin()),
                        Select::make('territory_id')
                            ->relationship('territory', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(self::visibleToAdmin()),
                        Select::make('status')
                            ->label(__('pios.product.status'))
                            ->options(fn (): array => (auth()->user()?->isAdmin() ?? false)
                                ? ProductStatus::options()
                                : collect(ProductStatus::options())->except(ProductStatus::Inactive->value)->all())
                            ->default(ProductStatus::Active->value)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Product Details')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('pios.product.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('coverage_area')
                            ->maxLength(255)
                            ->visible(self::visibleToAdmin()),
                        TextInput::make('certification')
                            ->maxLength(255)
                            ->visible(self::visibleToAdmin()),
                        Textarea::make('description')
                            ->label(__('pios.product.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('selling_price')
                            ->label(__('pios.product.price'))
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('RM'),
                        TextInput::make('stock_quantity')
                            ->label(__('pios.product.stock'))
                            ->helperText(__('pios.product.stock_hint'))
                            ->numeric()
                            ->minValue(0)
                            ->integer(),
                        TextInput::make('sales_commission')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('RM')
                            ->visible(self::visibleToAdmin()),
                        Select::make('vendor_id')
                            ->label(__('pios.product.vendor'))
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(self::visibleToAdmin()),
                        TextInput::make('cost')
                            ->label(__('pios.product.cost'))
                            ->numeric()
                            ->prefix('RM')
                            ->visible(self::visibleToAdmin()),
                        KeyValue::make('pricing_structure')
                            ->columnSpanFull()
                            ->visible(self::visibleToAdmin()),
                    ])
                    ->columns(2),

                Section::make('Media and Promotion')
                    ->schema([
                        FileUpload::make('media_assets')
                            ->label(__('pios.product.media'))
                            ->helperText(__('pios.product.media_hint'))
                            ->multiple()
                            ->image()
                            ->reorderable()
                            ->openable()
                            ->downloadable()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->maxSize(20480)
                            ->acceptedFileTypes([
                                'image/png', 'image/jpeg', 'image/webp',
                                'video/mp4', 'video/webm', 'video/quicktime',
                            ])
                            ->columnSpanFull(),
                        KeyValue::make('promotional_content')
                            ->columnSpanFull()
                            ->visible(self::visibleToAdmin()),
                    ]),
            ]);
    }

    /**
     * Business Owners get a simplified product form (name, selling_price, image,
     * description, category, status). Commercial-confidential fields (cost,
     * vendor, sales_commission, territory, etc.) are reserved for HQ/admin users
     * and never exposed to a Business Owner.
     */
    private static function visibleToAdmin(): Closure
    {
        return fn (): bool => auth()->user()?->isAdmin() ?? false;
    }
}
