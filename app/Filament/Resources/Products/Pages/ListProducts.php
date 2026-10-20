<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getSubheading(): ?string
    {
        $user = auth()->user();

        if ($user?->isBusinessOwner() && $user->businessOwner) {
            $count = $user->businessOwner->productCount();

            return "Product limit: {$count} / ".Product::MAX_PER_BUSINESS_OWNER.' products used';
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isBoAtLimit = $user?->isBusinessOwner()
            && ($user->businessOwner?->hasReachedProductLimit() ?? false);

        return [
            CreateAction::make()
                ->disabled($isBoAtLimit)
                ->tooltip($isBoAtLimit ? 'Maximum limit of '.Product::MAX_PER_BUSINESS_OWNER.' products reached' : null),
        ];
    }
}
