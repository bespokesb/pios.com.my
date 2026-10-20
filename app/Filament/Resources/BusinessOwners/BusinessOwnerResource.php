<?php

namespace App\Filament\Resources\BusinessOwners;

use App\Filament\Resources\BusinessOwners\Pages\CreateBusinessOwner;
use App\Filament\Resources\BusinessOwners\Pages\EditBusinessOwner;
use App\Filament\Resources\BusinessOwners\Pages\ListBusinessOwners;
use App\Filament\Resources\BusinessOwners\Schemas\BusinessOwnerForm;
use App\Filament\Resources\BusinessOwners\Tables\BusinessOwnersTable;
use App\Models\BusinessOwner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BusinessOwnerResource extends Resource
{
    protected static ?string $model = BusinessOwner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Register';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.register');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.business_owners');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return BusinessOwnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessOwnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CustomersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessOwners::route('/'),
            'create' => CreateBusinessOwner::route('/create'),
            'edit' => EditBusinessOwner::route('/{record}/edit'),
        ];
    }
}
