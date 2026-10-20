<?php

namespace App\Filament\Resources\BusinessOwners\Tables;

use App\Enums\RecordStatus;
use App\Models\BusinessOwner;
use App\Services\AuditLogger;
use App\Services\RegistrationApprover;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BusinessOwnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business_owner_no')
                    ->label('Business Owner No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('applicant_name')
                    ->searchable(),
                TextColumn::make('partner.applicant_name')
                    ->label('Partner')
                    ->searchable(),
                TextColumn::make('hp_no')
                    ->label('HP No')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->searchable(),
                IconColumn::make('is_amway_agent')
                    ->label(__('pios.businessowner.amway_agent.label'))
                    ->boolean()
                    ->sortable(),
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
                    ->modalDescription('Approve and activate this Business Owner registration?')
                    ->visible(fn (BusinessOwner $record): bool => $record->status === RecordStatus::Pending
                        && (auth()->user()?->can('approve', $record) ?? false))
                    ->action(function (BusinessOwner $record): void {
                        app(RegistrationApprover::class)->approveBusinessOwner($record);

                        Notification::make()
                            ->success()
                            ->title('Business Owner approved')
                            ->send();
                    }),
                // Only the managing Partner (and HQ) verifies whether a BO is an
                // Amway agent — the BO can never self-certify (verifyAmwayAgent
                // policy, distinct from update where a BO may edit its own record).
                Action::make('verifyAmwayAgent')
                    ->label(__('pios.businessowner.amway_agent.action'))
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn (BusinessOwner $record): bool => auth()->user()?->can('verifyAmwayAgent', $record) ?? false)
                    ->modalHeading(__('pios.businessowner.amway_agent.action'))
                    ->schema([
                        Toggle::make('is_amway_agent')
                            ->label(__('pios.businessowner.amway_agent.label'))
                            ->helperText(__('pios.businessowner.amway_agent.hint'))
                            ->default(fn (BusinessOwner $record): bool => (bool) $record->is_amway_agent),
                    ])
                    ->action(function (BusinessOwner $record, array $data): void {
                        abort_unless(auth()->user()?->can('verifyAmwayAgent', $record) ?? false, 403);

                        $old = $record->getOriginal();
                        $record->update(['is_amway_agent' => (bool) ($data['is_amway_agent'] ?? false)]);

                        app(AuditLogger::class)->log(
                            actor: auth()->user(),
                            event: 'amway_agent_verified',
                            auditable: $record,
                            oldValues: $old,
                            newValues: $record->fresh()->getAttributes(),
                        );

                        Notification::make()->success()->title(__('pios.businessowner.amway_agent.updated'))->send();
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
