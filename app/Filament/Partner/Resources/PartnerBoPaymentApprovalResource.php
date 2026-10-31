<?php

namespace App\Filament\Partner\Resources;

use App\Filament\Partner\Resources\Pages\ListPartnerBoPaymentApprovals;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PartnerBoPaymentApprovalResource extends Resource
{
    protected static ?string $model = SubscriptionPayment::class;

    protected static ?string $slug = 'bo-payment-approvals';

    protected static ?string $navigationLabel = 'Pengesahan Bayaran BO';

    protected static ?string $modelLabel = 'Pengesahan Bayaran BO';

    protected static ?string $pluralModelLabel = 'Pengesahan Bayaran Business Owner';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isPartner() || $user->isSysAdmin());
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $partner = $user?->partner;

        if (! $partner) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        // Show payments made by Business Owners sponsored by this Partner
        return parent::getEloquentQuery()
            ->where('partner_id', $partner->id)
            ->where('payer_role', 'business_owner');
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

                TextColumn::make('businessOwner.applicant_name')
                    ->label('Nama Business Owner')
                    ->weight('bold')
                    ->description(fn (SubscriptionPayment $record): string => ($record->businessOwner?->business_owner_no ?? '').' • '.($record->businessOwner?->hp_no ?? ''))
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
                        'approved' => 'Disahkan & Lulus',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu Pengesahan Anda',
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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending_approval' => 'Menunggu Pengesahan',
                        'approved' => 'Disahkan & Lulus',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Sahkan Bayaran')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Sahkan Pembayaran Business Owner')
                    ->modalDescription(fn (SubscriptionPayment $record): string => "Adakah anda mengesahkan penerimaan bayaran RM {$record->amount} (Kaedah: ".ucfirst($record->payment_method).") daripada {$record->payerName()}? Langganan {$record->product?->name} akan diaktifkan serta-merta selama 30 hari dan resit rasmi dikeluarkan.")
                    ->action(function (SubscriptionPayment $record): void {
                        app(SubscriptionService::class)->approvePayment($record, auth()->user());

                        Notification::make()
                            ->title('Bayaran Business Owner Disahkan')
                            ->body("Langganan {$record->payerName()} telah diaktifkan dan resit rasmi {$record->receipt_no} telah dikeluarkan.")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isPending())
                    ->form([
                        Textarea::make('reason')
                            ->label('Sebab Penolakan')
                            ->placeholder('Contoh: Tunai belum diserahkan / Slip tidak sah.')
                            ->required(),
                    ])
                    ->action(function (SubscriptionPayment $record, array $data): void {
                        app(SubscriptionService::class)->rejectPayment($record, auth()->user(), $data['reason']);

                        Notification::make()
                            ->title('Bayaran Ditolak')
                            ->body('Status bayaran telah ditolak.')
                            ->danger()
                            ->send();
                    }),

                Action::make('receipt')
                    ->label('Lihat Resit')
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
            'index' => ListPartnerBoPaymentApprovals::route('/'),
        ];
    }
}
