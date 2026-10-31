<?php

namespace App\Filament\Resources\SubscriptionProducts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SubscriptionProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pelan / Produk')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Kod')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Harga (RM)')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('period_days')
                    ->label('Tempoh')
                    ->suffix(' Hari')
                    ->sortable(),

                TextColumn::make('target_role')
                    ->label('Sasaran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'partner' => 'Partner Sahaja',
                        'business_owner' => 'Business Owner Sahaja',
                        default => 'Semua (Partner & BO)',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'partner' => 'warning',
                        'business_owner' => 'info',
                        default => 'success',
                    }),

                IconColumn::make('is_mandatory')
                    ->label('Wajib')
                    ->boolean(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Susunan')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dicipta')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
