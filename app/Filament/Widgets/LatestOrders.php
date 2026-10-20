<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\IncomingOrders\IncomingOrderResource;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('pios.widgets.latest_orders'))
            ->query(
                fn (): Builder => Order::query()
                    ->visibleTo($this->resolveUser())
                    ->with(['customer', 'businessOwner', 'items'])
                    ->latest('ordered_at')
                    ->latest('id'),
            )
            // In /business the row links straight to the incoming order detail.
            ->recordUrl(fn (Order $record): ?string => Filament::getCurrentPanel()?->getId() === 'business'
                ? IncomingOrderResource::getUrl('view', ['record' => $record])
                : null)
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('order_no')
                    ->label(__('pios.widgets.order_no'))
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('customer.name')
                    ->label(__('pios.widgets.customer'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('items_summary')
                    ->label(__('pios.order.field.items_summary'))
                    ->state(fn (Order $record): ?string => $record->items->isEmpty() ? null
                        : $record->items->first()->product_name
                            .($record->items->count() > 1 ? ' +'.($record->items->count() - 1) : '')
                            .' ×'.$record->items->sum('quantity'))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('businessOwner.applicant_name')
                    ->label(__('pios.widgets.business_owner'))
                    // The BO is always themselves in /business — pure noise there.
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() !== 'business')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('pios.widgets.status'))
                    ->badge(),
                TextColumn::make('total')
                    ->label(__('pios.widgets.total'))
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('ordered_at')
                    ->label(__('pios.widgets.ordered_at'))
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable(),
            ]);
    }

    protected function resolveUser(): User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : new User;
    }
}
