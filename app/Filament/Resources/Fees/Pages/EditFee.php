<?php

namespace App\Filament\Resources\Fees\Pages;

use App\Enums\FeeType;
use App\Filament\Resources\Fees\FeeResource;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditFee extends EditRecord
{
    protected static string $resource = FeeResource::class;

    private array $oldValues = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldValues = $this->record->getOriginal();

        return $this->normalizeFeeScope($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'updated',
            auditable: $this->record,
            oldValues: $this->oldValues,
            newValues: $this->record->fresh()->getAttributes(),
        );
    }

    private function normalizeFeeScope(array $data): array
    {
        $customer = Customer::query()->find($data['customer_id'] ?? null);

        if ($customer) {
            $data['business_owner_id'] = $customer->business_owner_id;
            $data['partner_id'] = $customer->partner_id;
        } elseif (filled($data['business_owner_id'] ?? null)) {
            $businessOwner = BusinessOwner::query()->find($data['business_owner_id']);

            if (! $businessOwner) {
                throw ValidationException::withMessages([
                    'data.business_owner_id' => 'Business Owner was not found.',
                ]);
            }

            $data['partner_id'] = $businessOwner->partner_id;
        }

        if (($data['fee_type'] ?? null) === FeeType::BusinessOwnerMonthly->value && blank($data['business_owner_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner is required for monthly Business Owner fees.',
            ]);
        }

        if (($data['fee_type'] ?? null) === FeeType::VipCustomer->value && blank($data['customer_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.customer_id' => 'Customer is required for VIP Customer fees.',
            ]);
        }

        if (blank($data['partner_id'] ?? null) && blank($data['business_owner_id'] ?? null) && blank($data['customer_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.partner_id' => 'At least one fee scope is required.',
            ]);
        }

        return $data;
    }
}
