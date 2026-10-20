<?php

namespace App\Filament\Resources\ProductProposals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductProposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('businessOwner.applicant_name')
                    ->label('Business Owner')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('area')
                    ->searchable(),
                TextColumn::make('partner_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('customer_price')
                    ->money()
                    ->sortable(),
                ImageColumn::make('image_video_path'),
                TextColumn::make('approval_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->searchable(),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
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
