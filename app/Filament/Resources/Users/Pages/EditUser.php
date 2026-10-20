<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use STS\FilamentImpersonate\Actions\Impersonate;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->record($this->getRecord())
                ->redirectTo(fn (User $record): string => $record->isBusinessOwner()
                    ? route('filament.business.pages.dashboard')
                    : route('filament.admin.pages.dashboard')),
            DeleteAction::make(),
        ];
    }
}
