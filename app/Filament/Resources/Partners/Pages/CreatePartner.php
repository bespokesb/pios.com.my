<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Filament\Resources\Partners\PartnerResource;
use App\Models\Partner;
use App\Services\AuditLogger;
use App\Services\BusinessOwnerUpgradeService;
use App\Services\IdentifierGenerator;
use App\Services\RegistrationAccountCreator;
use App\Services\WhatsAppPassSender;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    private array $accountData = [];

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->extraAttributes([
                'wire:loading.attr' => 'disabled',
                'wire:target' => 'create',
                'x-on:click' => 'setTimeout(() => { $el.disabled = true; }, 0)',
            ]);
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->extraAttributes([
                'wire:loading.attr' => 'disabled',
                'wire:target' => 'createAnother',
                'x-on:click' => 'setTimeout(() => { $el.disabled = true; }, 0)',
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->accountData = [
            'create_user' => (bool) ($data['create_user'] ?? false),
            'account_password' => filled($data['account_password'] ?? null) ? (string) $data['account_password'] : null,
        ];

        unset($data['create_user'], $data['account_password']);

        $user = auth()->user();

        if ($user?->actsAsPartner()) {
            if (! $user->partner) {
                throw ValidationException::withMessages([
                    'data.applicant_name' => 'Your user account is not linked to a Partner profile.',
                ]);
            }

            // A partner may only recruit directly beneath themselves.
            if (! $user->partner->hasOpenDirectSlot()) {
                throw ValidationException::withMessages([
                    'data.applicant_name' => sprintf(
                        'You already have the maximum of %d direct partners. '
                        .'Remove an existing partner before adding another.',
                        Partner::MAX_DIRECT_PARTNERS,
                    ),
                ]);
            }

            $data['parent_partner_id'] = $user->partner->id;
            // No owner approval needed for partner recruited by parent partner:
            $data['status'] = RecordStatus::Active->value;
        }

        // Advisory early error for any creator (admins pick parent_partner_id in
        // the form). The authoritative, race-safe check is in handleRecordCreation().
        if (! empty($data['parent_partner_id'])) {
            $parent = Partner::find($data['parent_partner_id']);
            if ($parent && ! $parent->hasOpenDirectSlot()) {
                throw ValidationException::withMessages([
                    'data.parent_partner_id' => sprintf(
                        '%s already has the maximum of %d direct partners.',
                        $parent->applicant_name,
                        Partner::MAX_DIRECT_PARTNERS,
                    ),
                ]);
            }
        }

        $this->validateDuplicateContact($data);

        $data['partner_no'] = app(IdentifierGenerator::class)->generate('partner', Partner::class, 'partner_no');
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Race-safe authoritative cap gate: lock the parent row, re-check its
        // open slot, then insert — so concurrent creators can't overflow to 5.
        return DB::transaction(function () use ($data): Model {
            // Guard against rapid duplicate submission (e.g. double click)
            $recentDuplicate = Partner::query()
                ->where('created_by', auth()->id())
                ->where('applicant_name', $data['applicant_name'])
                ->where('created_at', '>=', now()->subSeconds(15))
                ->latest()
                ->first();

            if ($recentDuplicate) {
                return $recentDuplicate;
            }

            $parentId = $data['parent_partner_id'] ?? null;

            if ($parentId) {
                $parent = Partner::query()->whereKey($parentId)->lockForUpdate()->first();

                if ($parent && ! $parent->hasOpenDirectSlot()) {
                    throw ValidationException::withMessages([
                        'data.parent_partner_id' => sprintf(
                            '%s already has the maximum of %d direct partners.',
                            $parent->applicant_name,
                            Partner::MAX_DIRECT_PARTNERS,
                        ),
                    ]);
                }
            }

            return Partner::query()->create($data);
        });
    }

    protected function afterCreate(): void
    {
        $password = filled($this->accountData['account_password'] ?? null)
            ? (string) $this->accountData['account_password']
            : null;

        if (($this->accountData['create_user'] ?? false) && blank($this->record->user_id)) {
            app(RegistrationAccountCreator::class)->createForPartner(
                $this->record,
                UserStatus::Active,
                $password,
            );
        }

        // A Partner is a Business Owner by default — provision the BO profile
        app(BusinessOwnerUpgradeService::class)->provisionFor($this->record->refresh());

        if ($password !== null) {
            $waUrl = WhatsAppPassSender::forPartner($this->record->fresh(), $password);

            if ($waUrl) {
                Notification::make()
                    ->success()
                    ->title('Partner Berjaya Didaftarkan')
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

    private function validateDuplicateContact(array $data): void
    {
        foreach (['ic_no' => 'IC No', 'hp_no' => 'HP No', 'email' => 'Email address'] as $field => $label) {
            if (blank($data[$field] ?? null)) {
                continue;
            }

            if (Partner::query()->where($field, $data[$field])->exists()) {
                throw ValidationException::withMessages([
                    "data.{$field}" => "{$label} already belongs to another Partner.",
                    $field => "{$label} already belongs to another Partner.",
                ]);
            }
        }
    }
}
