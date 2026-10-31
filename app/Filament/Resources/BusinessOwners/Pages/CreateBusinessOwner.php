<?php

namespace App\Filament\Resources\BusinessOwners\Pages;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Filament\Resources\BusinessOwners\BusinessOwnerResource;
use App\Models\BusinessOwner;
use App\Services\AuditLogger;
use App\Services\IdentifierGenerator;
use App\Services\RegistrationAccountCreator;
use App\Services\RegistrationApprover;
use App\Services\WhatsAppPassSender;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateBusinessOwner extends CreateRecord
{
    protected static string $resource = BusinessOwnerResource::class;

    private array $accountData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->accountData = [
            'create_user' => (bool) ($data['create_user'] ?? false),
            'account_password' => $data['account_password'] ?? null,
        ];

        unset($data['create_user'], $data['account_password']);

        $data['partner_id'] = $this->resolvePartnerId($data);

        if (auth()->user()?->actsAsPartner()) {
            // No owner approval needed! Business Owners are active immediately.
            $data['status'] = RecordStatus::Active->value;
        }

        $this->validateDuplicateContact($data);

        $data['business_owner_no'] = app(IdentifierGenerator::class)->generate(
            'business_owner',
            BusinessOwner::class,
            'business_owner_no',
        );
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return BusinessOwner::query()->create($data);
    }

    /**
     * DP-6: after creating a Business Owner, return to the list. A partner-created
     * BO is Pending and the partner has no edit access to it, so the default
     * redirect-to-edit left them on a dead page; the index always resolves.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $password = filled($this->accountData['account_password'] ?? null)
            ? (string) $this->accountData['account_password']
            : null;

        if ($this->accountData['create_user'] ?? false) {
            app(RegistrationAccountCreator::class)->createForBusinessOwner(
                $this->record,
                UserStatus::Active,
                $password,
            );
        }

        app(RegistrationApprover::class)->generateSetupFee($this->record);

        if ($password !== null) {
            $waUrl = WhatsAppPassSender::forBusinessOwner($this->record->fresh(), $password);

            if ($waUrl) {
                Notification::make()
                    ->success()
                    ->title('Business Owner Berjaya Didaftarkan')
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
            event: 'created',
            auditable: $this->record,
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
        foreach (['ic_no' => 'IC No', 'hp_no' => 'HP No'] as $field => $label) {
            if (blank($data[$field] ?? null)) {
                continue;
            }

            if (BusinessOwner::query()
                ->where('partner_id', $data['partner_id'])
                ->where($field, $data[$field])
                ->exists()) {
                throw ValidationException::withMessages([
                    "data.{$field}" => "{$label} already belongs to another Business Owner for this Partner.",
                ]);
            }
        }
    }
}
