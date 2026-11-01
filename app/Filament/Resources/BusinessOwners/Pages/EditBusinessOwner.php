<?php

namespace App\Filament\Resources\BusinessOwners\Pages;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Filament\Resources\BusinessOwners\BusinessOwnerResource;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Services\AuditLogger;
use App\Services\PartnerUpgradeService;
use App\Services\RegistrationAccountCreator;
use App\Services\RegistrationApprover;
use App\Services\WhatsAppPassSender;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditBusinessOwner extends EditRecord
{
    protected static string $resource = BusinessOwnerResource::class;

    private array $accountData = [];

    private array $oldValues = [];

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->extraAttributes([
                'wire:loading.attr' => 'disabled',
                'wire:target' => 'save',
                'x-on:click' => 'setTimeout(() => { $el.disabled = true; }, 0)',
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendPassWhatsApp')
                ->label('Send Pass (WhatsApp)')
                ->color('success')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->modalHeading(fn (): string => 'Send Pass via WhatsApp — '.$this->record->applicant_name)
                ->modalDescription('Tetapkan kata laluan dan buka WhatsApp (wa.me) untuk menghantar maklumat akses kepada Business Owner.')
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
                ->action(function (array $data): void {
                    $password = (string) $data['password'];

                    if ($this->record->user) {
                        $this->record->user->forceFill([
                            'password' => Hash::make($password),
                            'status' => UserStatus::Active,
                        ])->save();
                    } else {
                        app(RegistrationAccountCreator::class)->createForBusinessOwner(
                            $this->record,
                            UserStatus::Active,
                            $password,
                        );
                    }

                    if ($this->record->status !== RecordStatus::Active) {
                        $this->record->forceFill(['status' => RecordStatus::Active])->save();
                    }

                    $waUrl = WhatsAppPassSender::forBusinessOwner($this->record->fresh(), $password);

                    if ($waUrl) {
                        $this->js('window.open('.json_encode($waUrl).", '_blank');");

                        Notification::make()
                            ->success()
                            ->title('Kata Laluan Dikemaskini')
                            ->body('Membuka WhatsApp untuk menghantar maklumat log masuk.')
                            ->persistent()
                            ->actions([
                                Action::make('reopenWa')
                                    ->label('Buka WhatsApp')
                                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                    ->color('success')
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
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalDescription('Approve and activate this Business Owner registration?')
                ->visible(fn (): bool => $this->record->status === RecordStatus::Pending
                    && (auth()->user()?->can('approve', $this->record) ?? false))
                ->action(function (): void {
                    app(RegistrationApprover::class)->approveBusinessOwner($this->record);

                    Notification::make()
                        ->success()
                        ->title('Business Owner approved')
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            Action::make('upgradeToPartner')
                ->label('Initiate Partner Upgrade')
                ->color('warning')
                ->icon('heroicon-o-arrow-trending-up')
                ->modalHeading('Appoint placement for the new Partner')
                ->modalDescription('Choose where in your line the upgraded Business Owner attaches. HQ must verify and approve before it activates.')
                ->visible(fn (): bool => auth()->user()?->can('initiatePartnerUpgrade', $this->record) ?? false)
                ->schema(function (): array {
                    $options = $this->placementOptions();

                    if ($options === []) {
                        return [
                            Placeholder::make('no_slots')
                                ->label('')
                                ->content(sprintf(
                                    'Your line is full — every partner from you down to the bottom already has the maximum of %d direct partners. Free a slot before upgrading this Business Owner.',
                                    Partner::MAX_DIRECT_PARTNERS,
                                )),
                        ];
                    }

                    return [
                        Select::make('placement_parent_id')
                            ->label('Place new partner under')
                            ->options($options)
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->helperText(sprintf(
                                'Only you and downline partners with an open slot (fewer than %d direct partners) are listed.',
                                Partner::MAX_DIRECT_PARTNERS,
                            )),
                    ];
                })
                ->action(function (array $data): void {
                    if (blank($data['placement_parent_id'] ?? null)) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot upgrade')
                            ->body('No available placement — your line has no open direct slot.')
                            ->send();

                        return;
                    }

                    try {
                        app(PartnerUpgradeService::class)->initiate($this->record, (int) $data['placement_parent_id']);
                    } catch (RegistrationApprovalException|ValidationException $e) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot upgrade')
                            ->body($e instanceof ValidationException
                                ? collect($e->errors())->flatten()->first()
                                : $e->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Partner upgrade submitted')
                        ->body('Pending HQ verification and approval.')
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * Placement targets for a BO→Partner appointment: the managing partner +
     * their downline nodes that still have an open direct slot.
     *
     * @return array<int, string>
     */
    private function placementOptions(): array
    {
        $managingPartner = $this->record->partner;

        if (! $managingPartner) {
            return [];
        }

        return Partner::eligiblePlacementParents($managingPartner)
            ->mapWithKeys(fn (Partner $p): array => [
                $p->getKey() => sprintf(
                    '%s — %s (%d/%d)',
                    $p->partner_no,
                    $p->applicant_name,
                    $p->directChildPartnersCount(),
                    Partner::MAX_DIRECT_PARTNERS,
                ),
            ])
            ->all();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->user_id !== null) {
            $data['create_user'] = true;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->getOriginal();
        $this->accountData = [
            'create_user' => (bool) ($data['create_user'] ?? false),
            'account_password' => filled($data['account_password'] ?? null) ? (string) $data['account_password'] : null,
        ];

        unset($data['create_user'], $data['account_password'], $data['business_owner_no']);

        $data['partner_id'] = $this->resolvePartnerId($data);
        $this->validateDuplicateContact($data);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        $password = $this->accountData['account_password'] ?? null;

        try {
            if ($this->record->user_id && $this->record->user) {
                if ($password !== null) {
                    $this->record->user->forceFill([
                        'password' => Hash::make($password),
                        'status' => UserStatus::Active,
                    ])->save();

                    if ($this->record->status !== RecordStatus::Active) {
                        $this->record->forceFill(['status' => RecordStatus::Active])->save();
                    }
                }

                if ($this->record->email && $this->record->email !== $this->record->user->email) {
                    $this->record->user->forceFill(['email' => $this->record->email])->save();
                }
            } elseif (($this->accountData['create_user'] ?? false) && blank($this->record->user_id)) {
                app(RegistrationAccountCreator::class)->createForBusinessOwner(
                    $this->record,
                    UserStatus::Active,
                    $password,
                );

                if ($this->record->status !== RecordStatus::Active) {
                    $this->record->forceFill(['status' => RecordStatus::Active])->save();
                }
            }
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('Gagal Mengemaskini Akaun')
                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                ->send();

            throw $e;
        }

        if ($password !== null) {
            $waUrl = WhatsAppPassSender::forBusinessOwner($this->record->fresh(), $password);

            if ($waUrl) {
                Notification::make()
                    ->success()
                    ->title('Kata Laluan Berjaya Disimpan')
                    ->body('Akses log masuk sedia untuk dihantar ke WhatsApp.')
                    ->persistent()
                    ->actions([
                        Action::make('sendWhatsApp')
                            ->label('Send Pass (WhatsApp)')
                            ->color('success')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->button()
                            ->url($waUrl, shouldOpenInNewTab: true),
                    ])
                    ->send();
            }
        }

        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'updated',
            auditable: $this->record,
            oldValues: $this->oldValues,
            newValues: $this->record->fresh()->getAttributes(),
        );
    }

    private function resolvePartnerId(array $data): int
    {
        $user = auth()->user();

        if ($user?->actsAsPartner()) {
            if (! $user->partner) {
                throw ValidationException::withMessages([
                    'data.partner_id' => 'Your user account is not linked to a Partner profile.',
                ]);
            }

            return $user->partner->id;
        }

        if (blank($data['partner_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.partner_id' => 'Partner is required.',
            ]);
        }

        return (int) $data['partner_id'];
    }

    private function validateDuplicateContact(array $data): void
    {
        foreach (['ic_no' => 'IC No', 'hp_no' => 'HP No', 'email' => 'Email address'] as $field => $label) {
            if (blank($data[$field] ?? null)) {
                continue;
            }

            if (BusinessOwner::query()
                ->where('partner_id', $data['partner_id'])
                ->where($field, $data[$field])
                ->whereKeyNot($this->record->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    "data.{$field}" => "{$label} already belongs to another Business Owner for this Partner.",
                    $field => "{$label} already belongs to another Business Owner for this Partner.",
                ]);
            }
        }
    }
}
