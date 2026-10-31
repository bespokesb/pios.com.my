<?php

namespace App\Filament\Resources\Partners\Tables;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Models\Partner;
use App\Services\BusinessOwnerUpgradeService;
use App\Services\RegistrationAccountCreator;
use App\Services\RegistrationApprover;
use App\Services\WhatsAppPassSender;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

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
                Action::make('sendPassWhatsApp')
                    ->label('Send Pass')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->modalHeading(fn (Partner $record): string => 'Send Pass via WhatsApp — '.$record->applicant_name)
                    ->modalDescription('Tetapkan kata laluan dan buka WhatsApp untuk menghantar maklumat akses kepada Partner.')
                    ->schema([
                        TextInput::make('password')
                            ->label('Kata Laluan')
                            ->required()
                            ->password()
                            ->revealable()
                            ->minLength(6)
                            ->default('password')
                            ->helperText('Kata laluan ini akan disimpan terus ke akaun Partner.'),
                    ])
                    ->action(function (Partner $record, array $data, $livewire): void {
                        $password = (string) $data['password'];

                        if ($record->user) {
                            $record->user->forceFill([
                                'password' => Hash::make($password),
                                'status' => UserStatus::Active,
                            ])->save();
                        } else {
                            app(RegistrationAccountCreator::class)->createForPartner(
                                $record,
                                UserStatus::Active,
                                $password,
                            );
                        }

                        if ($record->status !== RecordStatus::Active) {
                            $record->forceFill(['status' => RecordStatus::Active])->save();
                        }

                        app(BusinessOwnerUpgradeService::class)->provisionFor($record->refresh());

                        $waUrl = WhatsAppPassSender::forPartner($record->fresh(), $password);

                        if ($waUrl) {
                            if (method_exists($livewire, 'js')) {
                                $livewire->js('window.open('.json_encode($waUrl).", '_blank');");
                            }

                            Notification::make()
                                ->success()
                                ->title('Kata Laluan Dikemaskini')
                                ->body('Maklumat akses sedia untuk dihantar ke WhatsApp.')
                                ->persistent()
                                ->actions([
                                    Action::make('openWa')
                                        ->label('Buka WhatsApp (Send Pass)')
                                        ->color('success')
                                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                        ->button()
                                        ->url($waUrl, shouldOpenInNewTab: true),
                                ])
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Kata Laluan Disimpan')
                                ->body('Nombor telefon tidak sah untuk pautan WhatsApp.')
                                ->send();
                        }
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
