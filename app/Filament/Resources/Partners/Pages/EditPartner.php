<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Filament\Pages\PartnerNetworkTree;
use App\Filament\Resources\Partners\PartnerResource;
use App\Filament\Widgets\PartnerDownlineTree;
use App\Models\Partner;
use App\Services\AuditLogger;
use App\Services\BusinessOwnerUpgradeService;
use App\Services\RegistrationAccountCreator;
use App\Services\RegistrationApprover;
use App\Services\WhatsAppPassSender;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    private array $accountData = [];

    private array $oldValues = [];

    protected function getFooterWidgets(): array
    {
        return [
            PartnerDownlineTree::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendPassWhatsApp')
                ->label('Send Pass (WhatsApp)')
                ->color('success')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->modalHeading(fn (): string => 'Send Pass via WhatsApp — '.$this->record->applicant_name)
                ->modalDescription('Tetapkan kata laluan dan buka WhatsApp (wa.me) untuk menghantar maklumat akses kepada Partner.')
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
                ->action(function (array $data): void {
                    $password = (string) $data['password'];

                    if ($this->record->user) {
                        $this->record->user->forceFill([
                            'password' => Hash::make($password),
                            'status' => UserStatus::Active,
                        ])->save();
                    } else {
                        app(RegistrationAccountCreator::class)->createForPartner(
                            $this->record,
                            UserStatus::Active,
                            $password,
                        );
                    }

                    if ($this->record->status !== RecordStatus::Active) {
                        $this->record->forceFill(['status' => RecordStatus::Active])->save();
                    }

                    app(BusinessOwnerUpgradeService::class)->provisionFor($this->record->refresh());

                    $waUrl = WhatsAppPassSender::forPartner($this->record->fresh(), $password);

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
            Action::make('viewNetwork')
                ->label(__('pios.partner.network.view_full'))
                ->icon('heroicon-o-share')
                ->color('gray')
                ->url(fn (): string => PartnerNetworkTree::getUrl())
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalDescription('Approve and activate this partner registration?')
                ->visible(fn (): bool => $this->record->status === RecordStatus::Pending
                    && (auth()->user()?->can('approve', $this->record) ?? false))
                ->action(function (): void {
                    try {
                        app(RegistrationApprover::class)->approvePartner($this->record);
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

                    $this->refreshFormData(['status']);
                }),
            Action::make('rejectRecruit')
                ->label('Reject / Withdraw')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalDescription('Reject this pending recruit and free the parent\'s direct slot? This cannot be undone.')
                ->visible(fn (): bool => $this->record->status === RecordStatus::Pending
                    && (auth()->user()?->isAdmin()
                        || (auth()->user()?->isPartner()
                            && auth()->user()?->partner?->id === $this->record->parent_partner_id)))
                ->action(function (): void {
                    $old = $this->record->getOriginal();
                    $this->record->forceFill(['status' => RecordStatus::Inactive])->save();

                    app(AuditLogger::class)->log(
                        actor: auth()->user(),
                        event: 'recruit_rejected',
                        auditable: $this->record,
                        oldValues: $old,
                        newValues: $this->record->fresh()->getAttributes(),
                    );

                    Notification::make()
                        ->success()
                        ->title('Recruit rejected')
                        ->body('The direct slot is now free.')
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            DeleteAction::make(),
        ];
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

        unset($data['create_user'], $data['account_password'], $data['partner_no']);

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
            app(RegistrationAccountCreator::class)->createForPartner(
                $this->record,
                UserStatus::Active,
                $password,
            );

            if ($this->record->status !== RecordStatus::Active) {
                $this->record->forceFill(['status' => RecordStatus::Active])->save();
            }
        }

        // Ensure active partner has BO profile provisioned
        app(BusinessOwnerUpgradeService::class)->provisionFor($this->record->refresh());

        if ($password !== null) {
            $waUrl = WhatsAppPassSender::forPartner($this->record->fresh(), $password);

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

    private function validateDuplicateContact(array $data): void
    {
        foreach (['ic_no' => 'IC No', 'hp_no' => 'HP No'] as $field => $label) {
            if (blank($data[$field] ?? null)) {
                continue;
            }

            if (Partner::query()->where($field, $data[$field])->whereKeyNot($this->record->id)->exists()) {
                throw ValidationException::withMessages([
                    "data.{$field}" => "{$label} already belongs to another Partner.",
                ]);
            }
        }
    }
}
