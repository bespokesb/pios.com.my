<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Business-panel listing of the logged-in Business Owner's own customers.
 *
 * Reads ONLY rows the BO owns via Customer::scopeVisibleTo (business_owner_id),
 * upholding the customer-ownership invariant. canView() restricts it to an
 * actual Business Owner so the AdminPanelProvider's discoverWidgets()
 * auto-registration never mounts it on the /admin or /partner dashboards.
 */
class BusinessCustomers extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isBusinessOwner() && $user->businessOwner !== null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('pios.widgets.my_customers'))
            ->query(
                fn (): Builder => Customer::query()
                    ->visibleTo($this->resolveUser())
                    ->latest('id'),
            )
            ->recordUrl(
                fn (Customer $record): ?string => CustomerResource::getUrl('view', ['record' => $record]),
            )
            ->emptyStateHeading(__('pios.customer.empty.heading'))
            ->emptyStateDescription(__('pios.customer.empty.hint'))
            ->emptyStateIcon('heroicon-o-user-plus')
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('customer_no')
                    ->label(__('pios.widgets.cust_no'))
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('name')
                    ->label(__('pios.widgets.cust_name'))
                    ->searchable(),
                TextColumn::make('hp_no')
                    ->label(__('pios.widgets.cust_phone'))
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label(__('pios.widgets.cust_email'))
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category')
                    ->label(__('pios.widgets.cust_category'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('pios.widgets.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('pios.widgets.cust_registered'))
                    ->date('d M Y')
                    ->sortable(),
            ]);
    }

    protected function resolveUser(): User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : new User;
    }
}
