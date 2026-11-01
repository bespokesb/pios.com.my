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
                    ->label('Plan / Product Name')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Price (MYR)')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('period_days')
                    ->label('Period')
                    ->suffix(' Days')
                    ->sortable(),

                TextColumn::make('target_role')
                    ->label('Target')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'partner' => 'Partner Only',
                        'business_owner' => 'Business Owner Only',
                        default => 'All (Partner & BO)',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'partner' => 'warning',
                        'business_owner' => 'info',
                        default => 'success',
                    }),

                IconColumn::make('is_mandatory')
                    ->label('Mandatory')
                    ->boolean(),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
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
