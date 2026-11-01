<?php

namespace App\Filament\Partner\Resources;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Filament\Partner\Resources\Pages\ListPartnerBoFeeApprovals;
use App\Models\Fee;
use App\Services\FeeService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PartnerBoFeeApprovalResource extends Resource
{
    protected static ?string $model = Fee::class;

    protected static ?string $slug = 'bo-fee-approvals';

    protected static ?string $navigationLabel = 'BO Fee Approvals';

    protected static ?string $modelLabel = 'BO Fee';

    protected static ?string $pluralModelLabel = 'Business Owner Fee Approvals';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Fees';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $partner = $user?->partner;

        if (! $partner && ! $user?->isSysAdmin()) {
            return null;
        }

        $count = Fee::query()
            ->when(! $user->isSysAdmin(), fn ($q) => $q->where('partner_id', $partner?->id))
            ->where('status', FeeStatus::Pending)
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

        return parent::getEloquentQuery()
            ->when(! $user->isSysAdmin(), fn (Builder $q) => $q->where('partner_id', $partner?->id))
            ->whereNotNull('business_owner_id')
            ->with(['businessOwner', 'payments' => fn ($q) => $q->latest()]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('businessOwner.applicant_name')
                    ->label('Business Owner')
                    ->weight('bold')
                    ->description(fn (Fee $record): string => ($record->businessOwner?->business_owner_no ?? '').' • '.($record->businessOwner?->hp_no ?? ''))
                    ->searchable(),

                TextColumn::make('fee_type')
                    ->label('Fee Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof FeeType ? $state->label() : ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('billing_period')
                    ->label('Period / Due')
                    ->description(fn (Fee $record): string => $record->due_date ? 'Due: '.$record->due_date->format('d M Y') : '')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount (MYR)')
                    ->money('MYR')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('payment_info')
                    ->label('Submitted Payment')
                    ->state(function (Fee $record): string {
                        $p = $record->payments->first();
                        if (! $p) {
                            return '—';
                        }
                        $method = match ($p->payment_type) {
                            'cash' => 'Cash',
                            'bank_transfer' => 'Bank Transfer',
                            'qr_code' => 'QR DuitNow',
                            default => ucfirst((string) $p->payment_type),
                        };

                        return $method.($p->reference_no ? " ({$p->reference_no})" : '');
                    })
                    ->description(fn (Fee $record): ?string => $record->payments->first()?->paid_at?->format('d M Y, h:i A')),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (FeeStatus|string $state): string => match ($state instanceof FeeStatus ? $state : FeeStatus::tryFrom((string) $state)) {
                        FeeStatus::Paid => 'Paid & Confirmed',
                        FeeStatus::Pending => 'Pending (Check Payment)',
                        FeeStatus::Overdue => 'Overdue',
                        FeeStatus::Waived => 'Waived',
                        default => 'Unpaid',
                    })
                    ->color(fn (FeeStatus|string $state): string => match ($state instanceof FeeStatus ? $state : FeeStatus::tryFrom((string) $state)) {
                        FeeStatus::Paid => 'success',
                        FeeStatus::Pending => 'warning',
                        FeeStatus::Overdue => 'danger',
                        FeeStatus::Waived => 'info',
                        default => 'gray',
                    })
                    ->tooltip('Klik untuk Check Payment & sahkan bayaran')
                    ->action(self::makeCheckPaymentAction('check_payment_from_status')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        FeeStatus::Pending->value => 'Pending Confirmation',
                        FeeStatus::Unpaid->value => 'Unpaid',
                        FeeStatus::Paid->value => 'Paid & Confirmed',
                        FeeStatus::Overdue->value => 'Overdue',
                    ]),
            ])
            ->actions([
                self::makeCheckPaymentAction('check_payment'),

                Action::make('approve')
                    ->label('Sahkan Bayaran')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Fee $record): bool => in_array($record->status, [FeeStatus::Pending, FeeStatus::Unpaid, FeeStatus::Overdue]))
                    ->modalHeading('Sahkan Bayaran Yuran Business Owner')
                    ->modalDescription(fn (Fee $record): string => "Sahkan bahawa {$record->businessOwner?->applicant_name} telah membayar yuran RM ".number_format((float) $record->amount, 2).'. Rekod bayaran akan disahkan.')
                    ->modalSubmitActionLabel('Sahkan Bayaran')
                    ->form([
                        Select::make('payment_method')
                            ->label('Kaedah Bayaran')
                            ->options([
                                'cash' => 'Tunai (Cash)',
                                'bank_transfer' => 'Pindahan Bank (Bank Transfer)',
                                'cheque' => 'Cek (Cheque)',
                            ])
                            ->default(fn (Fee $record): string => $record->payments->first()?->payment_type ?: 'cash')
                            ->required(),

                        TextInput::make('reference_no')
                            ->label('No. Rujukan / Resit (Pilihan)')
                            ->default(fn (Fee $record): ?string => $record->payments->first()?->reference_no),

                        Textarea::make('notes')
                            ->label('Catatan (Pilihan)')
                            ->placeholder('cth: Bayaran tunai diterima semasa lawatan'),
                    ])
                    ->action(function (Fee $record, array $data): void {
                        app(FeeService::class)->approvePayment(
                            fee: $record,
                            actor: auth()->user(),
                            paymentMethod: $data['payment_method'] ?? 'cash',
                            referenceNo: $data['reference_no'] ?? null,
                            notes: $data['notes'] ?? null
                        );

                        Notification::make()
                            ->title('Bayaran Yuran Disahkan')
                            ->body('Bayaran yuran RM '.number_format((float) $record->amount, 2)." untuk {$record->businessOwner?->applicant_name} telah berjaya disahkan.")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Fee $record): bool => $record->status === FeeStatus::Pending)
                    ->modalHeading('Tolak Bayaran Yuran')
                    ->modalDescription('Nyatakan sebab penolakan bayaran ini (contoh: bayaran belum diterima atau resit tidak sah).')
                    ->form([
                        Textarea::make('reason')
                            ->label('Sebab Penolakan')
                            ->placeholder('cth: Tunai belum diserahkan / resit tidak sah')
                            ->required(),
                    ])
                    ->action(function (Fee $record, array $data): void {
                        app(FeeService::class)->rejectPayment(
                            fee: $record,
                            actor: auth()->user(),
                            reason: $data['reason']
                        );

                        Notification::make()
                            ->title('Bayaran Yuran Ditolak')
                            ->body('Status yuran telah dikembalikan kepada belum bayar.')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function makeCheckPaymentAction(string $name = 'check_payment'): Action
    {
        return Action::make($name)
            ->label('Check Payment')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('info')
            ->modalHeading(fn (Fee $record): string => "Check Payment — {$record->businessOwner?->applicant_name}")
            ->modalDescription('Semak info bayaran yuran yang dihantar oleh Business Owner. Anda boleh mengesahkan bayaran di sini atau melalui butang Sahkan Bayaran di halaman.')
            ->modalSubmitAction(function (Fee $record, Action $action) {
                if ($record->status === FeeStatus::Paid) {
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
                    ->default(fn (Fee $record): string => "{$record->businessOwner?->applicant_name} ({$record->businessOwner?->business_owner_no}) — Tel: {$record->businessOwner?->hp_no}"),

                TextInput::make('fee_summary')
                    ->label('Maklumat Yuran & Jumlah')
                    ->disabled()
                    ->dehydrated(false)
                    ->default(fn (Fee $record): string => ($record->fee_type?->label() ?? 'Yuran').' — RM '.number_format((float) $record->amount, 2).($record->billing_period ? " ({$record->billing_period})" : '')),

                TextInput::make('submitted_time')
                    ->label('Tarikh Bayaran Dihantar')
                    ->disabled()
                    ->dehydrated(false)
                    ->default(fn (Fee $record): string => $record->payments->first()?->paid_at ? $record->payments->first()->paid_at->format('d M Y, h:i A') : 'Belum dihantar / Bayaran tunai'),

                Select::make('payment_method')
                    ->label('Kaedah Bayaran Dihantar')
                    ->options([
                        'cash' => 'Tunai (Cash)',
                        'bank_transfer' => 'Pindahan Bank (Online)',
                        'cheque' => 'Cek (Cheque)',
                    ])
                    ->default(fn (Fee $record): string => $record->payments->first()?->payment_type ?: 'bank_transfer')
                    ->required(),

                TextInput::make('reference_no')
                    ->label('No. Rujukan / Resit')
                    ->default(fn (Fee $record): ?string => $record->payments->first()?->reference_no)
                    ->placeholder('cth: FPX-12345 / Resit Tunai'),

                Textarea::make('notes')
                    ->label('Catatan Pengesahan (Pilihan)')
                    ->placeholder('cth: Bayaran telah disemak dalam akaun bank'),
            ])
            ->action(function (Fee $record, array $data): void {
                app(FeeService::class)->approvePayment(
                    fee: $record,
                    actor: auth()->user(),
                    paymentMethod: $data['payment_method'] ?? 'cash',
                    referenceNo: $data['reference_no'] ?? null,
                    notes: $data['notes'] ?? null
                );

                Notification::make()
                    ->title('Bayaran Yuran Disahkan')
                    ->body('Bayaran yuran RM '.number_format((float) $record->amount, 2)." untuk {$record->businessOwner?->applicant_name} telah berjaya disahkan.")
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerBoFeeApprovals::route('/'),
        ];
    }
}
