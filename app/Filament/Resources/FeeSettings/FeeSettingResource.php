<?php

namespace App\Filament\Resources\FeeSettings;

use App\Filament\Resources\FeeSettings\Pages\CreateFeeSetting;
use App\Filament\Resources\FeeSettings\Pages\EditFeeSetting;
use App\Filament\Resources\FeeSettings\Pages\ListFeeSettings;
use App\Filament\Resources\FeeSettings\Schemas\FeeSettingForm;
use App\Filament\Resources\FeeSettings\Tables\FeeSettingsTable;
use App\Models\FeeSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FeeSettingResource extends Resource
{
    protected static ?string $model = FeeSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.fee_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return FeeSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeeSettingsTable::configure($table);
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
            'index' => ListFeeSettings::route('/'),
            'create' => CreateFeeSetting::route('/create'),
            'edit' => EditFeeSetting::route('/{record}/edit'),
        ];
    }
}
