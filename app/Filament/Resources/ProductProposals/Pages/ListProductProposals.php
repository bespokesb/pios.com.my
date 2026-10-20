<?php

namespace App\Filament\Resources\ProductProposals\Pages;

use App\Filament\Resources\ProductProposals\ProductProposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductProposals extends ListRecords
{
    protected static string $resource = ProductProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
