<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Models\Subscription;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscription_no')
                    ->label('Subscription No')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscriber')
                    ->label('Subscriber')
                    ->state(fn (Subscription $record): string => $record->subscriberName().' ('.$record->subscriberIdentifier().')')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('businessOwner', function ($q) use ($search): void {
                            $q->where('applicant_name', 'like', "%{$search}%")
                                ->orWhere('business_owner_no', 'like', "%{$search}%");
                        })->orWhereHas('partner', function ($q) use ($search): void {
                            $q->where('applicant_name', 'like', "%{$search}%")
                                ->orWhere('partner_no', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('subscriber_type')
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
                    ->label('Product / Plan')
                    ->weight('medium')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Subscription $record): string => match ($record->status) {
                        'active' => $record->isActive() ? 'Active' : 'Expired',
                        'pending_approval' => 'Pending Approval',
                        'pending_payment' => 'Pending Payment',
                        'trial' => 'Trial (48h)',
                        default => ucfirst($record->status),
                    })
                    ->color(fn (Subscription $record): string => match ($record->status) {
                        'active' => $record->isActive() ? 'success' : 'danger',
                        'trial' => $record->isActive() ? 'amber' : 'danger',
                        'pending_approval' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('days_left')
                    ->label('Days Remaining')
                    ->state(fn (Subscription $record): string => $record->isActive() ? ($record->daysRemaining().' Days') : '-')
                    ->color(fn (Subscription $record): string => $record->daysRemaining() <= 5 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('expires_at')
                    ->label('Expiry Date')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('last_renewed_at')
                    ->label('Last Renewed')
                    ->dateTime('d M Y')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expires_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'trial' => 'Trial',
                        'pending_approval' => 'Pending Approval',
                        'pending_payment' => 'Pending Payment',
                    ]),

                SelectFilter::make('subscriber_type')
                    ->label('Role')
                    ->options([
                        'partner' => 'Partner',
                        'business_owner' => 'Business Owner',
                    ]),

                SelectFilter::make('subscription_product_id')
                    ->label('Product / Plan')
                    ->relationship('product', 'name'),
            ]);
    }
}
