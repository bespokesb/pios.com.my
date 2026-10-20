<?php

namespace App\Filament\Resources\ProductProposals;

use App\Filament\Resources\ProductProposals\Pages\CreateProductProposal;
use App\Filament\Resources\ProductProposals\Pages\EditProductProposal;
use App\Filament\Resources\ProductProposals\Pages\ListProductProposals;
use App\Filament\Resources\ProductProposals\Schemas\ProductProposalForm;
use App\Filament\Resources\ProductProposals\Tables\ProductProposalsTable;
use App\Models\ProductProposal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductProposalResource extends Resource
{
    protected static ?string $model = ProductProposal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Business Owner';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.business_owner');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.product_proposals');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductProposalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductProposalsTable::configure($table);
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
            'index' => ListProductProposals::route('/'),
            'create' => CreateProductProposal::route('/create'),
            'edit' => EditProductProposal::route('/{record}/edit'),
        ];
    }
}
