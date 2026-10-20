<?php

namespace App\Filament\Resources\BusinessOwners\RelationManagers;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only listing of a Business Owner's customers, shown on the BO edit page.
 *
 * The relationship query (BusinessOwner::customers) is inherently scoped to the
 * owner record, and the BO edit page itself is reached only through the
 * resource's visibleTo() query — so a partner sees only their downstream BOs'
 * customers. Customers are created/edited from the Customers module, so this
 * manager is read-only; each row links to the customer's detail page.
 */
class CustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'customers';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('pios.widgets.bo_customers');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn (Customer $record): ?string => CustomerResource::getUrl('view', ['record' => $record]),
            )
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('customer_no')
                    ->label(__('pios.widgets.cust_no'))
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('pios.widgets.cust_name'))
                    ->searchable(),
                TextColumn::make('hp_no')
                    ->label(__('pios.widgets.cust_phone'))
                    ->placeholder('—'),
                TextColumn::make('category')
                    ->label(__('pios.widgets.cust_category'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('pios.widgets.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('pios.widgets.bo_registered'))
                    ->date()
                    ->sortable(),
            ]);
    }
}
