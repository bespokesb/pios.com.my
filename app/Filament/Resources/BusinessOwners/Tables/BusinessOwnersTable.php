<?php

namespace App\Filament\Resources\BusinessOwners\Tables;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Filament\Partner\Resources\PartnerBoPaymentApprovalResource;
use App\Models\BusinessOwner;
use App\Services\AuditLogger;
use App\Services\RegistrationAccountCreator;
use App\Services\RegistrationApprover;
use App\Services\WhatsAppPassSender;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

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
                TextColumn::make('bge_status')
                    ->label('BGE Status')
                    ->state(function (BusinessOwner $record): string {
                        $pendingPayment = $record->subscriptionPayments()->where('status', 'pending_approval')->latest()->first();
                        if ($pendingPayment) {
                            return 'Pending Confirmation';
                        }
                        $bgeSub = $record->subscriptions()->whereHas('product', fn ($q) => $q->where('code', 'bge'))->first();
                        if (! $bgeSub) {
                            return 'Tiada Rekod';
                        }
                        if ($bgeSub->isActive()) {
                            return $bgeSub->isTrial() ? 'Trial Aktif' : 'Aktif';
                        }

                        return 'Tamat Tempoh';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Trial Aktif' => 'info',
                        'Pending Confirmation' => 'warning',
                        'Tamat Tempoh' => 'danger',
                        default => 'gray',
                    }),
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
                Action::make('check_bge_payment')
                    ->label('Sahkan Langganan BGE')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->visible(fn (BusinessOwner $record): bool => auth()->user()?->isPartner() && (bool) $record->subscriptionPayments()->where('status', 'pending_approval')->exists())
                    ->url(fn (BusinessOwner $record): string => PartnerBoPaymentApprovalResource::getUrl('index')),
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
                Action::make('sendPassWhatsApp')
                    ->label('Send Pass')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->modalHeading(fn (BusinessOwner $record): string => 'Send Pass via WhatsApp — '.$record->applicant_name)
                    ->modalDescription('Tetapkan kata laluan dan buka WhatsApp untuk menghantar maklumat akses.')
                    ->schema([
                        TextInput::make('password')
                            ->label('Kata Laluan')
                            ->required()
                            ->password()
                            ->revealable()
                            ->minLength(6)
                            ->default('password')
                            ->helperText('Kata laluan ini akan disimpan terus ke akaun Business Owner.'),
                    ])
                    ->action(function (BusinessOwner $record, array $data, $livewire): void {
                        $password = (string) $data['password'];

                        if ($record->user) {
                            $record->user->forceFill([
                                'password' => Hash::make($password),
                                'status' => UserStatus::Active,
                            ])->save();
                        } else {
                            app(RegistrationAccountCreator::class)->createForBusinessOwner(
                                $record,
                                UserStatus::Active,
                                $password,
                            );
                        }

                        if ($record->status !== RecordStatus::Active) {
                            $record->forceFill(['status' => RecordStatus::Active])->save();
                        }

                        $waUrl = WhatsAppPassSender::forBusinessOwner($record->fresh(), $password);

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
