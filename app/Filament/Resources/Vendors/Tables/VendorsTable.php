<?php

namespace App\Filament\Resources\Vendors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('pios.vendor.name'))
                    ->searchable(),
                TextColumn::make('contact_name')
                    ->label(__('pios.vendor.contact_name'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('pios.vendor.phone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('pios.vendor.email'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('pios.vendor.status'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
