<?php

namespace App\Filament\Partner\Resources;

use App\Filament\Partner\Resources\Pages\ListPartnerSubscriptions;
use App\Models\SubscriptionPayment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PartnerSubscriptionResource extends Resource
{
    protected static ?string $model = SubscriptionPayment::class;

    protected static ?string $slug = 'subscriptions';

    protected static ?string $navigationLabel = 'Langganan Saya';

    protected static ?string $modelLabel = 'Langganan Rakan Kongsi';

    protected static ?string $pluralModelLabel = 'Langganan & Bayaran Saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isPartner() || $user->isSysAdmin());
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->where('user_id', $user?->id)
            ->where('payer_role', 'partner');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_no')
                    ->label('No. Bayaran')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('receipt_no')
                    ->label('No. Resit')
                    ->badge()
                    ->color('success')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Pelan / Produk')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Jumlah (RM)')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Kaedah')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'qr_code' => 'QR DuitNow',
                        'cash' => 'Tunai (Cash)',
                        'bank_transfer' => 'Bank Transfer',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'qr_code' => 'success',
                        'cash' => 'amber',
                        default => 'primary',
                    }),

                ImageColumn::make('payment_proof')
                    ->label('Slip')
                    ->disk('public')
                    ->square(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disahkan & Aktif',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu Pengesahan Owner',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('paid_at')
                    ->label('Tarikh Bayar')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('approver.name')
                    ->label('Disahkan Oleh (Owner)')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('receipt')
                    ->label('Cetak Resit')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('info')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isApproved() && filled($record->receipt_no))
                    ->url(fn (SubscriptionPayment $record): string => route('subscriptions.receipt', $record->payment_no))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerSubscriptions::route('/'),
        ];
    }
}
