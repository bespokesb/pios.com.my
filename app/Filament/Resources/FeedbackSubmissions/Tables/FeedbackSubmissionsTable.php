<?php

namespace App\Filament\Resources\FeedbackSubmissions\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeedbackSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tarikh')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hp_no')
                    ->label('HP No')
                    ->searchable(),

                TextColumn::make('partner_id_input')
                    ->label('Partner ID Input')
                    ->placeholder('—')
                    ->searchable(),

                IconColumn::make('q1_income_growth')
                    ->label('Q1')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('q2_seeking_way')
                    ->label('Q2')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('q3_interested_tools')
                    ->label('Q3')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('remarks')
                    ->label('Komen')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('assigned_to_owner')
                    ->label('Owner')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedUser)
                    ->falseIcon(Heroicon::OutlinedUsers)
                    ->trueColor('warning')
                    ->falseColor('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }
}
