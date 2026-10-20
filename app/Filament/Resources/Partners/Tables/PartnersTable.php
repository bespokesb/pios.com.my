<?php

namespace App\Filament\Resources\Partners\Tables;

use App\Enums\RecordStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Models\Partner;
use App\Services\RegistrationApprover;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('partner_no')
                    ->label('Partner No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('applicant_name')
                    ->searchable(),
                TextColumn::make('hp_no')
                    ->label('HP No')
                    ->searchable(),
                TextColumn::make('partnerTier.name')
                    ->label('Tier')
                    ->searchable(),
                TextColumn::make('territory.name')
                    ->label('Territory')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Login')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
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
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalDescription('Approve and activate this partner registration?')
                    ->visible(fn (Partner $record): bool => $record->status === RecordStatus::Pending
                        && (auth()->user()?->can('approve', $record) ?? false))
                    ->action(function (Partner $record): void {
                        try {
                            app(RegistrationApprover::class)->approvePartner($record);
                        } catch (RegistrationApprovalException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot approve')
                                ->body($e->getMessage())
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Partner approved')
                            ->send();
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
