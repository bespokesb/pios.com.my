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
                    ->label('Payment No')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('receipt_no')
                    ->label('Receipt No')
                    ->badge()
                    ->color('success')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payer')
                    ->label('Payer')
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
                    ->label('Role')
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
                    ->label('Plan / Product')
                    ->weight('medium')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount (MYR)')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('period_days')
                    ->label('Duration')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => "{$state} Days")
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
                        default => 'Pending Approval',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('paid_at')
                    ->label('Payment Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                SelectFilter::make('payer_role')
                    ->label('Payer Role')
                    ->options([
                        'partner' => 'Partner',
                        'business_owner' => 'Business Owner',
                    ]),

                SelectFilter::make('subscription_product_id')
                    ->label('Product / Plan')
                    ->relationship('product', 'name'),
            ])
            ->actions([
                // PIOS Owner only approves Partner payments; Business Owner payments are handled exclusively by their sponsoring Partner
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isPending() && $record->payer_role === 'partner')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Subscription Payment')
                    ->modalDescription(fn (SubscriptionPayment $record): string => "Are you sure you want to approve payment of RM {$record->amount} for {$record->product?->name}? Subscription will be activated/extended for {$record->period_days} days and an official receipt generated.")
                    ->action(function (SubscriptionPayment $record): void {
                        app(SubscriptionService::class)->approvePayment($record, auth()->user());

                        Notification::make()
                            ->title('Subscription Payment Approved')
                            ->body("Payment for {$record->payerName()} approved. Official Receipt {$record->receipt_no} generated.")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (SubscriptionPayment $record): bool => $record->isPending() && $record->payer_role === 'partner')
                    ->form([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->placeholder('e.g., Unverified transaction slip / Incorrect amount.')
                            ->required(),
                    ])
                    ->action(function (SubscriptionPayment $record, array $data): void {
                        app(SubscriptionService::class)->rejectPayment($record, auth()->user(), $data['reason']);

                        Notification::make()
                            ->title('Payment Rejected')
                            ->body('Payment request marked as rejected.')
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
            ]);
    }
}
