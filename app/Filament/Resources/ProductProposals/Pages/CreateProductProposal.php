<?php

namespace App\Filament\Resources\ProductProposals\Pages;

use App\Enums\ProductProposalStatus;
use App\Filament\Resources\ProductProposals\ProductProposalResource;
use App\Models\BusinessOwner;
use App\Models\ProductProposal;
use App\Services\AuditLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateProductProposal extends CreateRecord
{
    protected static string $resource = ProductProposalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['reviewed_by'], $data['reviewed_at']);

        $data['business_owner_id'] = $this->resolveBusinessOwnerId($data);
        $data['approval_status'] = ProductProposalStatus::Pending->value;
        $data['reviewed_by'] = null;
        $data['reviewed_at'] = null;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return ProductProposal::query()->create($data);
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

    private function resolveBusinessOwnerId(array $data): int
    {
        $user = auth()->user();

        if ($user?->isBusinessOwner()) {
            if (! $user->businessOwner) {
                throw ValidationException::withMessages([
                    'data.business_owner_id' => 'Your user account is not linked to a Business Owner profile.',
                ]);
            }

            return $user->businessOwner->id;
        }

        if (blank($data['business_owner_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner is required.',
            ]);
        }

        if (! BusinessOwner::query()->whereKey($data['business_owner_id'])->exists()) {
            throw ValidationException::withMessages([
                'data.business_owner_id' => 'Business Owner was not found.',
            ]);
        }

        return (int) $data['business_owner_id'];
    }
}
