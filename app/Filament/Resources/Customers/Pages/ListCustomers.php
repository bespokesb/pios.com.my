<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Enums\CustomerCategory;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('pios.customer.segments.all'))
                ->badge(fn (): int => $this->stageCount(null)),
            'lead' => Tab::make(__('pios.customer.segments.lead'))
                ->badge(fn (): int => $this->stageCount(CustomerCategory::Lead))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('category', CustomerCategory::Lead)),
            'prospect' => Tab::make(__('pios.customer.segments.prospect'))
                ->badge(fn (): int => $this->stageCount(CustomerCategory::Prospect))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('category', CustomerCategory::Prospect)),
            'customer' => Tab::make(__('pios.customer.segments.customer'))
                ->badge(fn (): int => $this->stageCount(CustomerCategory::Customer))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('category', CustomerCategory::Customer)),
            'vip' => Tab::make(__('pios.customer.segments.vip'))
                ->badge(fn (): int => CustomerResource::getEloquentQuery()->where('is_vip', true)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_vip', true)),
        ];
    }

    private function stageCount(?CustomerCategory $category): int
    {
        $query = CustomerResource::getEloquentQuery();

        return $category
            ? $query->where('category', $category)->count()
            : $query->count();
    }
}
