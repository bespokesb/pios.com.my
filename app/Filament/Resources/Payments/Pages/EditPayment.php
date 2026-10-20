<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Fee;
use App\Models\Order;
use App\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

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
        $data = $this->validatePaymentTarget($data);
        $data['recorded_by'] = auth()->id();

        if (($data['status'] ?? null) === PaymentStatus::Verified->value && blank($data['paid_at'] ?? null)) {
            $data['paid_at'] = now();
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

    private function validatePaymentTarget(array $data): array
    {
        $hasFee = filled($data['fee_id'] ?? null);
        $hasOrder = filled($data['order_id'] ?? null);

        if ($hasFee === $hasOrder) {
            throw ValidationException::withMessages([
                'data.fee_id' => 'Choose exactly one Fee or Order target.',
                'data.order_id' => 'Choose exactly one Fee or Order target.',
            ]);
        }

        if ($hasFee && ! Fee::query()->whereKey($data['fee_id'])->exists()) {
            throw ValidationException::withMessages([
                'data.fee_id' => 'Fee was not found.',
            ]);
        }

        if ($hasOrder && ! Order::query()->whereKey($data['order_id'])->exists()) {
            throw ValidationException::withMessages([
                'data.order_id' => 'Order was not found.',
            ]);
        }

        $data['fee_id'] = $hasFee ? $data['fee_id'] : null;
        $data['order_id'] = $hasOrder ? $data['order_id'] : null;

        return $data;
    }
}
