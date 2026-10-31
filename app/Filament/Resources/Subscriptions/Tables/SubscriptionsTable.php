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
                    ->label('No. Langganan')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subscriber')
                    ->label('Pelanggan')
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
                    ->label('Produk / Pelan')
                    ->weight('medium')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Subscription $record): string => match ($record->status) {
                        'active' => $record->isActive() ? 'Aktif' : 'Tamat Tempoh',
                        'pending_approval' => 'Menunggu Pengesahan',
                        'pending_payment' => 'Menunggu Bayaran',
                        default => ucfirst($record->status),
                    })
                    ->color(fn (Subscription $record): string => match ($record->status) {
                        'active' => $record->isActive() ? 'success' : 'danger',
                        'pending_approval' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('days_left')
                    ->label('Baki Hari')
                    ->state(fn (Subscription $record): string => $record->isActive() ? ($record->daysRemaining().' Hari') : '-')
                    ->color(fn (Subscription $record): string => $record->daysRemaining() <= 5 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('expires_at')
                    ->label('Tarikh Luput')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('last_renewed_at')
                    ->label('Tarikh Diperbaharui')
                    ->dateTime('d M Y')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expires_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'pending_approval' => 'Menunggu Pengesahan',
                        'pending_payment' => 'Menunggu Bayaran',
                    ]),

                SelectFilter::make('subscriber_type')
                    ->label('Peranan')
                    ->options([
                        'partner' => 'Partner',
                        'business_owner' => 'Business Owner',
                    ]),

                SelectFilter::make('subscription_product_id')
                    ->label('Produk / Pelan')
                    ->relationship('product', 'name'),
            ]);
    }
}
