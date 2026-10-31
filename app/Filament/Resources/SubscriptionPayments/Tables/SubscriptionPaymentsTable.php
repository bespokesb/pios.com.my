<?php

namespace App\Filament\Resources\SubscriptionPayments\Tables;

use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionPaymentsTable
{
    public static function configure(Table $table): Table
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
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payer')
                    ->label('Pembayar')
                    ->state(fn (SubscriptionPayment $record): string => $record->payerName().' ('.$record->payerIdentifier().')')
                    ->description(fn (SubscriptionPayment $record): ?string => $record->payerHpNo())
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('businessOwner', function ($q) use ($search): void {
                            $q->where('applicant_name', 'like', "%{$search}%")
                                ->orWhere('business_owner_no', 'like', "%{$search}%");
                        })->orWhereHas('partner', function ($q) use ($search): void {
                            $q->where('applicant_name', 'like', "%{$search}%")
                                ->orWhere('partner_no', 'like', "%{$search}%");
                        })->orWhereHas('user', function ($q) use ($search): void {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('payer_role')
                    ->label('Peranan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'partner' => 'Partner',
                        'business_owner' => 'Business Owner',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'partner' => 'warning',
                        'business_owner' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('product.name')
                    ->label('Pelan / Produk')
                    ->weight('medium')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Kaedah')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'qr_code' => 'QR DuitNow',
                        'cash' => 'Tunai',
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
                        default => 'Menunggu Pengesahan',
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
                    ->label('Disahkan Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
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

                SelectFilter::make('payer_role')
                    ->label('Peranan Pembayar')
                    ->options([
                        'partner' => 'Partner',
                        'business_owner' => 'Business Owner',
                    ]),

                SelectFilter::make('subscription_product_id')
                    ->label('Produk / Pelan')
                    ->relationship('product', 'name'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Sahkan')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Sahkan Pembayaran Langganan')
                    ->modalDescription(fn (SubscriptionPayment $record): string => "Adakah anda pasti ingin mengesahkan bayaran RM {$record->amount} bagi {$record->product?->name}? Langganan akan diaktifkan/dilanjutkan selama {$record->period_days} hari dan resit rasmi akan dikeluarkan.")
                    ->action(function (SubscriptionPayment $record): void {
                        app(SubscriptionService::class)->approvePayment($record, auth()->user());

                        Notification::make()
                            ->title('Bayaran Telah Disahkan')
                            ->body("Resit rasmi {$record->receipt_no} telah dikeluarkan dan langganan telah diaktifkan.")
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
                            ->placeholder('Contoh: Slip tidak jelas / Bayaran tidak diterima dalam akaun.')
                            ->required(),
                    ])
                    ->action(function (SubscriptionPayment $record, array $data): void {
                        app(SubscriptionService::class)->rejectPayment($record, auth()->user(), $data['reason']);

                        Notification::make()
                            ->title('Bayaran Ditolak')
                            ->body('Status bayaran telah dikemaskini kepada ditolak.')
                            ->danger()
                            ->send();
                    }),

                Action::make('receipt')
                    ->label('Cetak Resit')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('info')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isApproved() && filled($record->receipt_no))
                    ->url(fn (SubscriptionPayment $record): string => route('subscriptions.receipt', $record->payment_no))
                    ->openUrlInNewTab(),
            ]);
    }
}
