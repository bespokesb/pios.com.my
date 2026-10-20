<?php

namespace App\Filament\Resources\PartnerTiers;

use App\Filament\Resources\PartnerTiers\Pages\CreatePartnerTier;
use App\Filament\Resources\PartnerTiers\Pages\EditPartnerTier;
use App\Filament\Resources\PartnerTiers\Pages\ListPartnerTiers;
use App\Filament\Resources\PartnerTiers\Schemas\PartnerTierForm;
use App\Filament\Resources\PartnerTiers\Tables\PartnerTiersTable;
use App\Models\PartnerTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PartnerTierResource extends Resource
{
    protected static ?string $model = PartnerTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.partner_tiers');
    }

    public static function form(Schema $schema): Schema
    {
        return PartnerTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnerTiersTable::configure($table);
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
            'index' => ListPartnerTiers::route('/'),
            'create' => CreatePartnerTier::route('/create'),
            'edit' => EditPartnerTier::route('/{record}/edit'),
        ];
    }
}
