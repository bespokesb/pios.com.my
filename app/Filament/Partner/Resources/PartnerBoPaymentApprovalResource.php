<?php

namespace App\Filament\Partner\Resources;

use App\Filament\Partner\Resources\Pages\ListPartnerBoPaymentApprovals;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use App\Services\WhatsAppPassSender;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    protected static ?string $navigationLabel = 'BO Subscription Approvals';

    protected static ?string $modelLabel = 'Pengesahan Langganan BGE';

    protected static ?string $pluralModelLabel = 'Pengesahan Bayaran Langganan BGE';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Subscriptions';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $partner = $user?->partner;

        if (! $partner && ! $user?->isSysAdmin()) {
            return null;
        }

        $count = SubscriptionPayment::query()
            ->when(! $user->isSysAdmin(), fn ($q) => $q->where('partner_id', $partner?->id))
            ->where('payer_role', 'business_owner')
            ->where('status', 'pending_approval')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isPartner() || $user->isSysAdmin());
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $partner = $user?->partner;

        if (! $partner && ! $user?->isSysAdmin()) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        // Show payments made by Business Owners sponsored by this Partner
        return parent::getEloquentQuery()
            ->when(! $user->isSysAdmin(), fn (Builder $q) => $q->where('partner_id', $partner?->id))
            ->where('payer_role', 'business_owner');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_no')
                    ->label('Payment No')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('receipt_no')
                    ->label('Receipt No')
                    ->badge()
                    ->color('success')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('businessOwner.applicant_name')
                    ->label('Business Owner')
                    ->weight('bold')
                    ->description(fn (SubscriptionPayment $record): string => ($record->businessOwner?->business_owner_no ?? '').' • '.($record->businessOwner?->hp_no ?? ''))
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Product / Plan')
                    ->searchable(),

                TextColumn::make('period_days')
                    ->label('Duration')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => "{$state} Days")
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount (MYR)')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'qr_code' => 'QR DuitNow',
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'qr_code' => 'success',
                        'cash' => 'amber',
                        default => 'primary',
                    }),

                ImageColumn::make('payment_proof')
                    ->label('Proof Slip')
                    ->disk('public')
                    ->square(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Approved & Active',
                        'rejected' => 'Rejected',
                        default => 'Pending Confirmation',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->action(
                        Action::make('check_payment')
                            ->label('Semak & Sahkan Bayaran')
                            ->modalHeading('Semakan Bayaran Langganan BGE (Business Owner)')
                            ->modalDescription('Semak maklumat bayaran langganan BizGrowth Engine (BGE) yang dihantar oleh Business Owner. Anda boleh mengesahkan atau menolak bayaran di sini.')
                            ->modalSubmitAction(function (SubscriptionPayment $record, Action $action) {
                                if ($record->isApproved()) {
                                    return false;
                                }

                                return $action
                                    ->label('Sahkan Bayaran Di Sini')
                                    ->color('success')
                                    ->icon(Heroicon::OutlinedCheckCircle);
                            })
                            ->form([
                                TextInput::make('bo_details')
                                    ->label('Business Owner')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn (SubscriptionPayment $record): string => "{$record->businessOwner?->applicant_name} ({$record->businessOwner?->business_owner_no}) — Tel: {$record->businessOwner?->hp_no}"),

                                TextInput::make('plan_summary')
                                    ->label('Pelan Langganan & Jumlah')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn (SubscriptionPayment $record): string => "{$record->product?->name} ({$record->period_days} Hari) — RM ".number_format((float) $record->amount, 2)),

                                TextInput::make('submitted_time')
                                    ->label('Tarikh Bayaran Dihantar')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn (SubscriptionPayment $record): string => $record->paid_at ? $record->paid_at->format('d M Y, h:i A') : $record->created_at->format('d M Y, h:i A')),

                                Select::make('months')
                                    ->label('Pakej Tempoh Langganan (Lanjutan Tempoh)')
                                    ->options([
                                        1 => '1 Bulan (30 Hari) — RM 30.00',
                                        2 => '2 Bulan (60 Hari) — RM 60.00',
                                        3 => '3 Bulan (90 Hari) — RM 90.00',
                                        6 => '6 Bulan (180 Hari) — RM 180.00',
                                        12 => '12 Bulan (360 Hari) — RM 360.00',
                                    ])
                                    ->default(fn (SubscriptionPayment $record): int => max(1, (int) round(($record->period_days ?: 30) / 30)))
                                    ->required(),

                                Select::make('payment_method')
                                    ->label('Kaedah Bayaran')
                                    ->options([
                                        'cash' => 'Tunai (Cash)',
                                        'bank_transfer' => 'Pindahan Bank (Online)',
                                        'qr_code' => 'QR DuitNow',
                                    ])
                                    ->default(fn (SubscriptionPayment $record): string => $record->payment_method ?: 'cash')
                                    ->required(),

                                TextInput::make('reference_no')
                                    ->label('No. Rujukan / Resit (Pilihan)')
                                    ->default(fn (SubscriptionPayment $record): ?string => $record->reference_no)
                                    ->placeholder('cth: FPX-12345 / Resit Tunai'),

                                Textarea::make('notes')
                                    ->label('Catatan Pengesahan (Pilihan)')
                                    ->default(fn (SubscriptionPayment $record): ?string => $record->notes)
                                    ->placeholder('cth: Tunai diterima dan akaun BGE diaktifkan'),
                            ])
                            ->action(function (SubscriptionPayment $record, array $data): void {
                                $months = max(1, (int) ($data['months'] ?? 1));
                                $days = $months * 30;
                                $amount = $months * 30.00;

                                $record->period_days = $days;
                                $record->amount = $amount;
                                $record->payment_method = $data['payment_method'] ?? $record->payment_method;
                                if (array_key_exists('reference_no', $data)) {
                                    $record->reference_no = $data['reference_no'];
                                }
                                if (array_key_exists('notes', $data)) {
                                    $record->notes = $data['notes'];
                                }
                                $record->save();

                                app(SubscriptionService::class)->approvePayment($record, auth()->user());

                                Notification::make()
                                    ->title('Bayaran Langganan Disahkan')
                                    ->body("Langganan {$record->payerName()} telah berjaya dilanjutkan selama {$days} hari. Resit Rasmi {$record->receipt_no} telah dijana.")
                                    ->success()
                                    ->send();
                            })
                    ),

                TextColumn::make('paid_at')
                    ->label('Payment Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending_approval' => 'Pending Confirmation',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Confirm & Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isPending())
                    ->modalHeading('Confirm Business Owner Payment')
                    ->modalDescription('Confirm receipt of payment and select the subscription duration bundle. The countdown will be extended and an official receipt generated.')
                    ->modalSubmitActionLabel('Confirm & Issue Receipt')
                    ->form([
                        Select::make('months')
                            ->label('Subscription Bundle')
                            ->options([
                                1 => '1 Month (30 Days) — RM 30.00',
                                2 => '2 Months (60 Days) — RM 60.00',
                                3 => '3 Months (90 Days) — RM 90.00',
                                6 => '6 Months (180 Days) — RM 180.00',
                                12 => '12 Months (360 Days) — RM 360.00',
                            ])
                            ->default(fn (SubscriptionPayment $record): int => max(1, (int) round(($record->period_days ?: 30) / 30)))
                            ->required(),

                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer (Online)',
                                'qr_code' => 'QR DuitNow',
                            ])
                            ->default(fn (SubscriptionPayment $record): string => $record->payment_method ?: 'cash')
                            ->required(),

                        TextInput::make('reference_no')
                            ->label('Reference No (Optional)')
                            ->default(fn (SubscriptionPayment $record): ?string => $record->reference_no),

                        Textarea::make('notes')
                            ->label('Notes (Optional)')
                            ->default(fn (SubscriptionPayment $record): ?string => $record->notes),
                    ])
                    ->action(function (SubscriptionPayment $record, array $data): void {
                        $months = max(1, (int) ($data['months'] ?? 1));
                        $days = $months * 30;
                        $amount = $months * 30.00;

                        $record->period_days = $days;
                        $record->amount = $amount;
                        $record->payment_method = $data['payment_method'] ?? $record->payment_method;
                        if (array_key_exists('reference_no', $data)) {
                            $record->reference_no = $data['reference_no'];
                        }
                        if (array_key_exists('notes', $data)) {
                            $record->notes = $data['notes'];
                        }
                        $record->save();

                        app(SubscriptionService::class)->approvePayment($record, auth()->user());

                        Notification::make()
                            ->title('Payment Confirmed & Approved')
                            ->body("{$record->payerName()}'s subscription has been extended by {$days} days. Official Receipt {$record->receipt_no} generated.")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isPending())
                    ->form([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->placeholder('e.g., Cash not received / Invalid payment slip.')
                            ->required(),
                    ])
                    ->action(function (SubscriptionPayment $record, array $data): void {
                        app(SubscriptionService::class)->rejectPayment($record, auth()->user(), $data['reason']);

                        Notification::make()
                            ->title('Payment Rejected')
                            ->body('Payment request has been marked as rejected.')
                            ->danger()
                            ->send();
                    }),

                Action::make('receipt')
                    ->label('View Receipt')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('info')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isApproved() && filled($record->receipt_no))
                    ->url(fn (SubscriptionPayment $record): string => route('subscriptions.receipt', $record->payment_no))
                    ->openUrlInNewTab(),

                Action::make('send_whatsapp')
                    ->label('Send Receipt (WhatsApp)')
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->color('success')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isApproved() && filled($record->receipt_no) && filled($record->businessOwner?->hp_no))
                    ->url(fn (SubscriptionPayment $record): string => WhatsAppPassSender::forSubscriptionReceiptToBo($record) ?? '#')
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
