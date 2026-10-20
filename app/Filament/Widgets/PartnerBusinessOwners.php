<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BusinessOwners\BusinessOwnerResource;
use App\Models\BusinessOwner;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Partner-panel listing of the logged-in partner's downstream Business Owners
 * (their accounts/clients). Reads ONLY rows the partner owns via
 * BusinessOwner::scopeVisibleTo, mirroring the authorization invariant used
 * everywhere else.
 *
 * canView() restricts it to an actual partner so the AdminPanelProvider's
 * discoverWidgets() auto-registration never mounts it (with cross-tenant or
 * empty data) on the /admin or /business dashboards.
 */
class PartnerBusinessOwners extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isPartner() && $user->partner !== null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('pios.widgets.my_business_owners'))
            ->query(
                fn (): Builder => BusinessOwner::query()
                    ->visibleTo($this->resolveUser())
                    ->withCount('customers')
                    ->latest('id'),
            )
            ->recordUrl(
                fn (BusinessOwner $record): ?string => BusinessOwnerResource::getUrl('edit', ['record' => $record]),
            )
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('business_owner_no')
                    ->label(__('pios.widgets.bo_no'))
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('applicant_name')
                    ->label(__('pios.widgets.bo_name'))
                    ->searchable(),
                TextColumn::make('company_name')
                    ->label(__('pios.widgets.bo_company'))
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('hp_no')
                    ->label(__('pios.widgets.bo_phone'))
                    ->placeholder('—'),
                TextColumn::make('customers_count')
                    ->label(__('pios.widgets.bo_customers'))
                    ->badge()
                    ->sortable()
                    ->color('info'),
                TextColumn::make('status')
                    ->label(__('pios.widgets.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('pios.widgets.bo_registered'))
                    ->date('d M Y')
                    ->sortable(),
            ]);
    }

    protected function resolveUser(): User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : new User;
    }
}
