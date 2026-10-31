<?php

namespace App\Filament\Resources\SubscriptionProducts\Pages;

use App\Filament\Resources\SubscriptionProducts\SubscriptionProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriptionProduct extends CreateRecord
{
    protected static string $resource = SubscriptionProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
