<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Services\AuditLogger;
use App\Services\IdentifierGenerator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['customer_no'], $data['partner_id']);

        $data = array_merge($data, $this->resolveOwnerIds($data));
        $this->validateVipFields($data);
        $this->validateDuplicateCustomer($data);

        $data['customer_no'] = app(IdentifierGenerator::class)->generate(
            'customer',
            Customer::class,
            'customer_no',
        );
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return Customer::query()->create($data);
    }

    protected function afterCreate(): void
    {
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'created',
            auditable: $this->record,
            newValues: $this->record->fresh()->getAttributes(),
        );
    }

    /**
     * @return array{business_owner_id: int|null, partner_id: int}
     */
    private function resolveOwnerIds(array $data): array
    {
        $user = auth()->user();

        if ($user?->actsAsBusinessOwner()) {
            if (! $user->businessOwner) {
                throw ValidationException::withMessages([
                    'data.business_owner_id' => 'Your user account is not linked to a Business Owner profile.',
                ]);
            }

            return [
                'business_owner_id' => $user->businessOwner->id,
                'partner_id' => $user->businessOwner->partner_id,
            ];
        }

        // A Partner creating a customer owns it directly — no Business Owner
        // (the legacy Partner-led customer flow, PRD §10.6).
        if ($user?->actsAsPartner()) {
            if (! $user->partner) {
                throw ValidationException::withMessages([
                    'data.business_owner_id' => 'Your user account is not linked to a Partner profile.',
                ]);
            }

            return [
                'business_owner_id' => null,
                'partner_id' => $user->partner->id,
            ];
        }

        if (blank($data['business_owner_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner is required.',
            ]);
        }

        $businessOwner = BusinessOwner::query()->find($data['business_owner_id']);

        if (! $businessOwner) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner was not found.',
            ]);
        }

        return [
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $businessOwner->partner_id,
        ];
    }

    private function validateVipFields(array $data): void
    {
        if ($this->isVipCustomer($data) && blank($data['abo_no'] ?? null)) {
            throw ValidationException::withMessages([
                'data.abo_no' => 'ABO No is required for VIP Customer (LOS).',
            ]);
        }
    }

    private function validateDuplicateCustomer(array $data): void
    {
        foreach (['hp_no' => 'HP No', 'kp_no' => 'IC/KP No'] as $field => $label) {
            if (blank($data[$field] ?? null)) {
                continue;
            }

            if (Customer::query()
                ->where('partner_id', $data['partner_id'])
                ->where($field, $data[$field])
                ->exists()) {
                throw ValidationException::withMessages([
                    "data.{$field}" => "{$label} already belongs to another Customer for this Partner.",
                ]);
            }
        }
    }

    private function isVipCustomer(array $data): bool
    {
        return (bool) ($data['is_vip'] ?? false);
    }
}
