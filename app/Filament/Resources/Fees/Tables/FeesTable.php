<?php

namespace App\Filament\Resources\Fees\Tables;

use App\Enums\FeeStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('businessOwner.applicant_name')
                    ->label('Business Owner')
                    ->searchable(),
                TextColumn::make('partner.applicant_name')
                    ->label('Partner')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('fee_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('billing_period')
                    ->label('Period')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
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
                SelectFilter::make('status')
                    ->options(FeeStatus::options()),
                Filter::make('overdue')
                    ->label('Overdue only')
                    ->query(fn ($query) => $query
                        ->whereIn('status', [FeeStatus::Unpaid->value, FeeStatus::Pending->value])
                        ->whereDate('due_date', '<', now()->toDateString())),
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
