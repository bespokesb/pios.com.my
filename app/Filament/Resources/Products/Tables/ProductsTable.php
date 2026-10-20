<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\CustomerCategory;
use App\Enums\ProductStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\UstazAiBlaster;
use App\Support\Phone;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        $isBusiness = Filament::getCurrentPanel()?->getId() === 'business';

        // Category (product_type) + status filters apply to every panel.
        $filters = [
            SelectFilter::make('product_type_id')
                ->label(__('pios.product.category'))
                ->relationship('productType', 'name')
                ->searchable()
                ->preload(),
            SelectFilter::make('status')
                ->label(__('pios.product.status'))
                ->options(ProductStatus::options()),
        ];

        $recordActions = [
            Action::make('share')
                ->label(__('pios.product.share.label'))
                ->icon('heroicon-o-share')
                ->color('success')
                ->visible(fn (Product $record): bool => auth()->user()?->can('share', $record) ?? false)
                // Only an active product has a live public page to share; the
                // action is greyed out with a tooltip for Draft/Inactive products.
                ->disabled(fn (Product $record): bool => $record->status !== ProductStatus::Active)
                ->tooltip(fn (Product $record): ?string => $record->status !== ProductStatus::Active
                    ? __('pios.product.share.only_active')
                    : null)
                ->modalHeading(__('pios.product.share.label'))
                ->schema([
                    TextEntry::make('link')
                        ->label(__('pios.product.share.link'))
                        ->state(fn (Product $record): string => $record->publicUrl())
                        ->copyable()
                        ->copyMessage(__('pios.product.share.copied')),
                    TextEntry::make('preview')
                        ->label(__('pios.product.share.preview'))
                        ->state(fn (Product $record): string => $record->shareMessage()),
                ])
                ->modalSubmitAction(false)
                ->extraModalFooterActions([
                    Action::make('openWhatsApp')
                        ->label(__('pios.product.share.open_whatsapp'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url(fn (Product $record): string => $record->shareWhatsAppUrl())
                        ->openUrlInNewTab(),
                ]),
            Action::make('blast')
                ->label(__('pios.product.blast.label'))
                ->icon('heroicon-o-megaphone')
                ->color('info')
                ->visible(fn (Product $record): bool => auth()->user()?->can('share', $record) ?? false)
                // DP-12: only an Active product may be blasted. The action is
                // shown but disabled (greyed out) for Draft/Inactive products,
                // with a tooltip explaining why.
                ->disabled(fn (Product $record): bool => $record->status !== ProductStatus::Active)
                ->tooltip(fn (Product $record): ?string => $record->status !== ProductStatus::Active
                    ? __('pios.product.blast.only_active')
                    : null)
                ->requiresConfirmation()
                ->modalHeading(__('pios.product.blast.label'))
                ->modalDescription(__('pios.product.blast.description'))
                ->modalSubmitActionLabel(__('pios.product.blast.submit'))
                ->schema([
                    Select::make('segment')
                        ->label(__('pios.product.blast.segment'))
                        ->options([
                            'all' => __('pios.product.blast.all'),
                            CustomerCategory::Lead->value => __('pios.customer.segments.lead'),
                            CustomerCategory::Prospect->value => __('pios.customer.segments.prospect'),
                            CustomerCategory::Customer->value => __('pios.customer.segments.customer'),
                            'vip' => __('pios.customer.segments.vip'),
                        ])
                        ->default('all')
                        ->live()
                        ->required(),
                    Placeholder::make('recipient_count')
                        ->hiddenLabel()
                        ->content(fn (Get $get): string => __('pios.product.blast.preview_count', [
                            'count' => self::blastRecipientsQuery($get('segment') ?? 'all')->count(),
                        ])),
                ])
                ->action(function (Product $record, array $data): void {
                    abort_unless(auth()->user()?->can('share', $record) ?? false, 403);

                    // DP-12: never blast a non-active product, even via a crafted request.
                    if ($record->status !== ProductStatus::Active) {
                        Notification::make()->warning()
                            ->title(__('pios.product.blast.only_active'))
                            ->send();

                        return;
                    }

                    $businessOwner = auth()->user()?->businessOwner;
                    $blaster = app(UstazAiBlaster::class);

                    if (! $blaster->isConfiguredFor($businessOwner)) {
                        Notification::make()->warning()
                            ->title(__('pios.product.blast.not_configured'))
                            ->body(__('pios.product.blast.not_configured_hint'))
                            ->send();

                        return;
                    }

                    // Recipients are the Business Owner's OWN customers (visibleTo),
                    // optionally narrowed to one stage, with a valid WhatsApp number.
                    // Shares the exact query the modal's recipient-count preview uses.
                    $recipients = self::blastRecipientsQuery($data['segment'] ?? 'all')->pluck('hp_no')
                        ->map(fn (?string $phone): ?string => Phone::toWhatsApp($phone))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    if ($recipients === []) {
                        Notification::make()->warning()
                            ->title(__('pios.product.blast.no_recipients'))
                            ->send();

                        return;
                    }

                    $result = $blaster->blast($businessOwner, $recipients, $record->shareMessage());

                    if ($result['ok']) {
                        app(AuditLogger::class)->log(
                            actor: auth()->user(),
                            event: 'whatsapp_blast',
                            auditable: $record,
                            newValues: ['recipients' => $result['sent'], 'segment' => $data['segment']],
                        );

                        Notification::make()->success()
                            ->title(__('pios.product.blast.sent', ['count' => $result['sent']]))
                            ->send();

                        return;
                    }

                    Notification::make()->danger()
                        ->title(__('pios.product.blast.failed'))
                        ->body($result['message'])
                        ->send();
                }),
            EditAction::make(),
        ];

        $toolbarActions = [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];

        // Eager-load productType to avoid N+1 when rendering the category badge.
        // Ownership scoping stays in ProductResource::getEloquentQuery()->visibleTo.
        $modifyQuery = fn (Builder $query): Builder => $query->with('productType');

        if ($isBusiness) {
            return $table
                ->modifyQueryUsing($modifyQuery)
                ->columns([
                    Stack::make([
                        ImageColumn::make('media_assets')
                            ->label(__('pios.product.image'))
                            ->disk('public')
                            ->state(fn (Product $record): ?string => $record->media_assets[0] ?? null)
                            ->height(160),
                        TextColumn::make('name')
                            ->label(__('pios.product.name'))
                            ->weight(FontWeight::Bold)
                            ->searchable(),
                        TextColumn::make('selling_price')
                            ->label(__('pios.product.price'))
                            ->money(),
                        TextColumn::make('stock_quantity')
                            ->label(__('pios.product.stock'))
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (?int $state): string => $state === null
                                ? __('pios.product.stock_untracked')
                                : (string) $state)
                            ->toggleable(),
                        TextColumn::make('productType.name')
                            ->label(__('pios.product.category'))
                            ->badge()
                            ->toggleable(),
                        TextColumn::make('status')
                            ->label(__('pios.product.status'))
                            ->badge(),
                    ]),
                ])
                ->contentGrid(['md' => 2, 'xl' => 3])
                ->emptyStateHeading(__('pios.product.empty.heading'))
                ->emptyStateDescription(__('pios.product.empty.hint'))
                ->emptyStateIcon('heroicon-o-cube')
                ->emptyStateActions([
                    CreateAction::make(),
                ])
                ->filters($filters)
                ->recordActions($recordActions)
                ->toolbarActions($toolbarActions);
        }

        return $table
            ->modifyQueryUsing($modifyQuery)
            ->columns([
                ImageColumn::make('media_assets')
                    ->label(__('pios.product.image'))
                    ->disk('public')
                    ->state(fn (Product $record): ?string => $record->media_assets[0] ?? null)
                    ->square(),
                TextColumn::make('product_no')
                    ->label('Product No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('productType.name')
                    ->label(__('pios.product.category'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('selling_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label(__('pios.product.stock'))
                    ->placeholder(__('pios.product.stock_untracked'))
                    ->sortable(),
                TextColumn::make('cost')
                    ->label(__('pios.product.cost'))
                    ->money()
                    ->sortable()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                TextColumn::make('vendor.name')
                    ->label(__('pios.product.vendor'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                TextColumn::make('margin')
                    ->label(__('pios.product.margin'))
                    ->state(fn (Product $record): ?string => $record->cost === null
                        ? null
                        : 'RM '.number_format((float) $record->selling_price - (float) $record->cost, 2))
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                TextColumn::make('coverage_area')
                    ->label('Coverage Area')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('territory.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('certification')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sales_commission')
                    ->money()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters($filters)
            ->recordActions($recordActions)
            ->toolbarActions($toolbarActions);
    }

    /**
     * The blast audience: the acting user's OWN customers (visibleTo) with a
     * phone number, optionally narrowed to a stage or the VIP flag. Used by BOTH
     * the modal's recipient-count preview and the send itself, so the number the
     * BO sees is exactly the number that gets messaged.
     */
    private static function blastRecipientsQuery(string $segment): Builder
    {
        $query = Customer::query()->visibleTo(auth()->user())->whereNotNull('hp_no');

        if ($segment === 'vip') {
            $query->where('is_vip', true);
        } elseif ($segment !== 'all') {
            $query->where('category', $segment);
        }

        return $query;
    }
}
