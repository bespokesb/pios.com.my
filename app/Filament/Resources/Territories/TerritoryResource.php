<?php

namespace App\Filament\Resources\Territories;

use App\Filament\Resources\Territories\Pages\CreateTerritory;
use App\Filament\Resources\Territories\Pages\EditTerritory;
use App\Filament\Resources\Territories\Pages\ListTerritories;
use App\Filament\Resources\Territories\Schemas\TerritoryForm;
use App\Filament\Resources\Territories\Tables\TerritoriesTable;
use App\Models\Territory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TerritoryResource extends Resource
{
    protected static ?string $model = Territory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.territories');
    }

    public static function form(Schema $schema): Schema
    {
        return TerritoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TerritoriesTable::configure($table);
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
            'index' => ListTerritories::route('/'),
            'create' => CreateTerritory::route('/create'),
            'edit' => EditTerritory::route('/{record}/edit'),
        ];
    }
}
