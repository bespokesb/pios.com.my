<?php

namespace App\Filament\Resources\Promotions\Tables;

use App\Enums\PromotionStatus;
use App\Models\Promotion;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('businessOwner.business_owner_no')
                    ->label('Business Owner No')
                    ->searchable(),
                TextColumn::make('businessOwner.applicant_name')
                    ->label('Business Owner')
                    ->searchable(),
                TextColumn::make('mode')
                    ->badge()
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('customers_count')
                    ->label('Customers')
                    ->counts('customers')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ran_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('run')
                    ->label('Run')
                    ->icon(Heroicon::RocketLaunch)
                    ->color('success')
                    ->visible(fn (Promotion $record): bool => (bool) auth()->user()?->can('update', $record)
                        && in_array($record->status, [PromotionStatus::Draft, PromotionStatus::Scheduled], true))
                    ->action(function (Promotion $record): void {
                        $record->update([
                            'status' => PromotionStatus::Running,
                            'ran_at' => now(),
                            'starts_at' => $record->starts_at ?: now(),
                        ]);

                        $record->recipients()->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
