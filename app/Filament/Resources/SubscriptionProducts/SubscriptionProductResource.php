<?php

namespace App\Filament\Resources\SubscriptionProducts;

use App\Filament\Resources\SubscriptionProducts\Pages\CreateSubscriptionProduct;
use App\Filament\Resources\SubscriptionProducts\Pages\EditSubscriptionProduct;
use App\Filament\Resources\SubscriptionProducts\Pages\ListSubscriptionProducts;
use App\Filament\Resources\SubscriptionProducts\Schemas\SubscriptionProductForm;
use App\Filament\Resources\SubscriptionProducts\Tables\SubscriptionProductsTable;
use App\Models\SubscriptionProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionProductResource extends Resource
{
    protected static ?string $model = SubscriptionProduct::class;

    protected static ?string $slug = 'subscription-products';

    protected static ?string $navigationLabel = 'Subscription Products';

    protected static ?string $modelLabel = 'Subscription Product';

    protected static ?string $pluralModelLabel = 'Subscription Products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Subscriptions';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSysAdmin() || $user->isPiosOwner());
    }

    public static function form(Schema $schema): Schema
    {
        return SubscriptionProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionProducts::route('/'),
            'create' => CreateSubscriptionProduct::route('/create'),
            'edit' => EditSubscriptionProduct::route('/{record}/edit'),
        ];
    }
}
