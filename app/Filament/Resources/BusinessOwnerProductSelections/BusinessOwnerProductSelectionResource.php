<?php

namespace App\Filament\Resources\BusinessOwnerProductSelections;

use App\Filament\Resources\BusinessOwnerProductSelections\Pages\CreateBusinessOwnerProductSelection;
use App\Filament\Resources\BusinessOwnerProductSelections\Pages\EditBusinessOwnerProductSelection;
use App\Filament\Resources\BusinessOwnerProductSelections\Pages\ListBusinessOwnerProductSelections;
use App\Filament\Resources\BusinessOwnerProductSelections\Schemas\BusinessOwnerProductSelectionForm;
use App\Filament\Resources\BusinessOwnerProductSelections\Tables\BusinessOwnerProductSelectionsTable;
use App\Models\BusinessOwnerProductSelection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BusinessOwnerProductSelectionResource extends Resource
{
    protected static ?string $model = BusinessOwnerProductSelection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Business Owner';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.business_owner');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.product_selections');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return BusinessOwnerProductSelectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessOwnerProductSelectionsTable::configure($table);
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
            'index' => ListBusinessOwnerProductSelections::route('/'),
            'create' => CreateBusinessOwnerProductSelection::route('/create'),
            'edit' => EditBusinessOwnerProductSelection::route('/{record}/edit'),
        ];
    }
}
