<?php

namespace App\Filament\Resources\IncomingOrders;

use App\Enums\OrderStatus;
use App\Filament\Resources\IncomingOrders\Pages\ListIncomingOrders;
use App\Filament\Resources\IncomingOrders\Pages\ViewIncomingOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\AuditLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Business-panel view of incoming manual orders (placed from the public product
 * page). Read-only except for status transitions; scoped strictly to the BO's
 * own orders via Order::scopeVisibleTo. No create — orders originate publicly.
 */
class IncomingOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Orders';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.orders');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.orders');
    }

    protected static ?string $modelLabel = 'Order';

    /**
     * Business panel only — the /admin panel already has a full Orders resource,
     * so this BO-facing, read+status view must not also appear there (it is
     * otherwise auto-discovered by AdminPanelProvider::discoverResources()).
     */
    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'business';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * "New orders" badge on the nav: count of Submitted orders awaiting action.
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        $count = $user instanceof User
            ? Order::query()->visibleTo($user)->where('status', OrderStatus::Submitted)->count()
            : 0;

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('pios.order.section.summary'))
                ->schema([
                    TextEntry::make('order_no')->label(__('pios.order.field.order_no')),
                    TextEntry::make('status')->label(__('pios.order.field.status'))->badge(),
                    TextEntry::make('customer.name')->label(__('pios.order.field.customer'))->placeholder('—'),
                    TextEntry::make('customer.hp_no')->label(__('pios.order.field.phone'))->placeholder('—'),
                    TextEntry::make('customer.address')->label(__('pios.order.field.address'))->placeholder('—')->columnSpanFull(),
                    TextEntry::make('total')->label(__('pios.order.field.total'))->money('MYR'),
                    TextEntry::make('ordered_at')->label(__('pios.order.field.ordered_at'))->dateTime('d M Y, H:i')->placeholder('—'),
                    TextEntry::make('tracking_number')->label(__('pios.order.field.tracking_number'))->placeholder('—')->copyable(),
                ])
                ->columns(2),

            Section::make(__('pios.order.section.items'))
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product_name')->label(__('pios.order.field.product')),
                            TextEntry::make('quantity')->label(__('pios.order.field.quantity')),
                            TextEntry::make('unit_price')->label(__('pios.order.field.unit_price'))->money('MYR'),
                            TextEntry::make('line_total')->label(__('pios.order.field.line_total'))->money('MYR'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ordered_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('items'))
            ->columns([
                TextColumn::make('order_no')->label(__('pios.order.field.order_no'))->searchable()->weight('semibold'),
                TextColumn::make('customer.name')->label(__('pios.order.field.customer'))->searchable()->placeholder('—'),
                TextColumn::make('items_summary')
                    ->label(__('pios.order.field.items_summary'))
                    ->state(fn (Order $record): ?string => $record->items->isEmpty() ? null
                        : $record->items->first()->product_name
                            .($record->items->count() > 1 ? ' +'.($record->items->count() - 1) : '')
                            .' ×'.$record->items->sum('quantity'))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('customer.hp_no')->label(__('pios.order.field.phone'))->placeholder('—'),
                TextColumn::make('total')->label(__('pios.order.field.total'))->money('MYR')->sortable(),
                TextColumn::make('status')->label(__('pios.order.field.status'))->badge(),
                TextColumn::make('tracking_number')->label(__('pios.order.field.tracking_number'))->placeholder('—')->toggleable(),
                TextColumn::make('ordered_at')->label(__('pios.order.field.ordered_at'))->dateTime('d M Y, H:i')->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('pios.order.field.status'))
                    ->options(OrderStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('invoice')
                    ->label(__('pios.order.document.invoice'))
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(fn (Order $record): bool => $record->status !== OrderStatus::Draft)
                    ->url(fn (Order $record): string => route('order.invoice', $record))
                    ->openUrlInNewTab(),
                Action::make('receipt')
                    ->label(__('pios.order.document.receipt'))
                    ->icon('heroicon-o-receipt-percent')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->paid_at !== null)
                    ->url(fn (Order $record): string => route('order.receipt', $record))
                    ->openUrlInNewTab(),
                Action::make('updateStatus')
                    ->label(__('pios.order.status_action.label'))
                    ->icon('heroicon-o-arrows-right-left')
                    // A terminal order (Completed/Cancelled) has nowhere to go.
                    ->visible(fn (Order $record): bool => ! $record->status->isTerminal())
                    ->modalHeading(__('pios.order.status_action.label'))
                    ->schema([
                        Select::make('status')
                            ->label(__('pios.order.field.status'))
                            // Forward-only: offer only the legal next steps (+ Cancel).
                            ->options(fn (Order $record): array => collect($record->status->nextStatuses())
                                ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])
                                ->all())
                            ->live()
                            ->required(),
                        TextInput::make('tracking_number')
                            ->label(__('pios.order.field.tracking_number'))
                            ->helperText(__('pios.order.status_action.tracking_hint'))
                            ->default(fn (Order $record): ?string => $record->tracking_number)
                            ->visible(fn (Get $get): bool => $get('status') === OrderStatus::Shipped->value)
                            ->required(fn (Get $get): bool => $get('status') === OrderStatus::Shipped->value)
                            ->maxLength(255),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $target = OrderStatus::from($data['status']);

                        // Defence-in-depth: reject an illegal transition server-side
                        // even if the option list was bypassed by a crafted request.
                        if (! $record->status->canTransitionTo($target)) {
                            Notification::make()->danger()->title(__('pios.order.status_action.illegal'))->send();

                            return;
                        }

                        $old = $record->getOriginal();
                        $record->transitionTo($target, $data['tracking_number'] ?? null);

                        app(AuditLogger::class)->log(
                            actor: auth()->user(),
                            event: 'order_status_updated',
                            auditable: $record,
                            oldValues: $old,
                            newValues: $record->fresh()->getAttributes(),
                        );

                        Notification::make()->success()->title(__('pios.order.status_action.updated'))->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncomingOrders::route('/'),
            'view' => ViewIncomingOrder::route('/{record}'),
        ];
    }
}
