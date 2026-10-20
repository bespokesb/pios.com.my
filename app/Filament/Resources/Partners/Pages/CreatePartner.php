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
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    private array $accountData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->accountData = [
            'create_user' => (bool) ($data['create_user'] ?? false),
        ];

        unset($data['create_user'], $data['account_password']);

        $user = auth()->user();

        if ($user?->actsAsPartner()) {
            if (! $user->partner) {
                throw ValidationException::withMessages([
                    'data.applicant_name' => 'Your user account is not linked to a Partner profile.',
                ]);
            }

            // A partner may only recruit directly beneath themselves, and the
            // recruit stays Pending until HQ approves it.
            if (! $user->partner->hasOpenDirectSlot()) {
                throw ValidationException::withMessages([
                    'data.applicant_name' => sprintf(
                        'You already have the maximum of %d direct partners (active or pending). '
                        .'Approve, reject, or remove an existing recruit before adding another.',
                        Partner::MAX_DIRECT_PARTNERS,
                    ),
                ]);
            }

            $data['parent_partner_id'] = $user->partner->id;
            $data['status'] = RecordStatus::Pending->value;
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
        if ($this->accountData['create_user'] ?? false) {
            app(RegistrationAccountCreator::class)->createForPartner(
                $this->record,
                $this->record->status === RecordStatus::Pending
                    ? UserStatus::Inactive
                    : UserStatus::Active,
            );
        }

        // A Partner is a Business Owner by default — provision the BO profile for an
        // active partner that has a login (no-op for Pending recruits, which get it
        // on HQ approval instead).
        app(BusinessOwnerUpgradeService::class)->provisionFor($this->record->refresh());

        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'created',
            auditable: $this->record,
            newValues: $this->record->fresh()->getAttributes(),
        );
    }

    private function validateDuplicateContact(array $data): void
    {
        foreach (['ic_no' => 'IC No', 'hp_no' => 'HP No'] as $field => $label) {
            if (blank($data[$field] ?? null)) {
                continue;
            }

            if (Partner::query()->where($field, $data[$field])->exists()) {
                throw ValidationException::withMessages([
                    "data.{$field}" => "{$label} already belongs to another Partner.",
                ]);
            }
        }
    }
}
