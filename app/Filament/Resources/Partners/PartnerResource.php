<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Resources\Partners\Schemas\PartnerForm;
use App\Filament\Resources\Partners\Tables\PartnersTable;
use App\Models\Partner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Register';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.register');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.partners');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return PartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DownlineRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
            'create' => CreatePartner::route('/create'),
            'edit' => EditPartner::route('/{record}/edit'),
        ];
    }
}
