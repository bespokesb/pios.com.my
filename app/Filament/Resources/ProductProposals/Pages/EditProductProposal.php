<?php

namespace App\Filament\Resources\ProductProposals\Pages;

use App\Enums\ProductProposalStatus;
use App\Filament\Resources\ProductProposals\ProductProposalResource;
use App\Models\BusinessOwner;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditProductProposal extends EditRecord
{
    protected static string $resource = ProductProposalResource::class;

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

        unset($data['reviewed_by'], $data['reviewed_at']);

        $data['business_owner_id'] = $this->resolveBusinessOwnerId($data);

        if (auth()->user()?->isBusinessOwner()) {
            $data['approval_status'] = $this->statusValue($this->record->approval_status);

            return $data;
        }

        $data['approval_status'] = $data['approval_status'] ?? $this->statusValue($this->record->approval_status);

        if ($this->isReviewedStatus($data['approval_status'])) {
            $data['reviewed_by'] = auth()->id();
            $data['reviewed_at'] = now();
        } elseif ($data['approval_status'] === ProductProposalStatus::Pending->value) {
            $data['reviewed_by'] = null;
            $data['reviewed_at'] = null;
        }

        return $data;
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

    private function isReviewedStatus(string $status): bool
    {
        return in_array($status, [
            ProductProposalStatus::UnderReview->value,
            ProductProposalStatus::Approved->value,
            ProductProposalStatus::Rejected->value,
        ], true);
    }

    private function statusValue(ProductProposalStatus|string|null $status): string
    {
        return $status instanceof ProductProposalStatus
            ? $status->value
            : (string) ($status ?: ProductProposalStatus::Pending->value);
    }
}
