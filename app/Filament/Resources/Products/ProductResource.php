<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.products');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.products');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user?->isBusinessOwner() && $user->businessOwner) {
            $count = $user->businessOwner->productCount();

            return "{$count}/".Product::MAX_PER_BUSINESS_OWNER;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $user = auth()->user();

        if ($user?->isBusinessOwner() && $user->businessOwner) {
            $count = $user->businessOwner->productCount();

            return $count >= Product::MAX_PER_BUSINESS_OWNER ? 'danger' : ($count >= 16 ? 'warning' : 'primary');
        }

        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
