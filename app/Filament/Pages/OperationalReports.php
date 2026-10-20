<?php

namespace App\Filament\Pages;

use App\Enums\CustomerCategory;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\ProductType;
use App\Models\Territory;
use App\Services\OperationalReportService;
use App\Services\PartnerQuotaService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class OperationalReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.nav.item.reports');
    }

    protected static ?string $navigationLabel = 'Operational Reports';

    protected string $view = 'filament.pages.operational-reports';

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?int $partner_id = null;

    public ?int $business_owner_id = null;

    public ?int $territory_id = null;

    public ?string $customer_category = null;

    public ?int $product_type_id = null;

    public ?string $status = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || $user?->isPartner() || $user?->isBusinessOwner());
    }

    public function clearFilters(): void
    {
        $this->date_from = null;
        $this->date_to = null;
        $this->partner_id = null;
        $this->business_owner_id = null;
        $this->territory_id = null;
        $this->customer_category = null;
        $this->product_type_id = null;
        $this->status = null;
    }

    public function getActiveFilterCountProperty(): int
    {
        return count($this->filters());
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->activeFilterCount > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportsProperty(): array
    {
        return app(OperationalReportService::class)->report(auth()->user(), $this->filters());
    }

    /**
     * Per-partner current-MYT-month customer attainment (count / target / percent),
     * scoped by visibleTo, computed with a SINGLE grouped COUNT keyed by partner_id
     * over the half-open [startOfMonth, +1 month) Asia/Kuala_Lumpur range — the same
     * boundary contract as PartnerQuotaService (Plan 01). No per-partner N+1 COUNT.
     *
     * @return array<int, array{partner_id: int, partner: string, partner_no: ?string, count: int, target: int, percent: int}>
     */
    public function getPartnerQuotaAttainmentProperty(): array
    {
        $user = auth()->user();

        $target = app(PartnerQuotaService::class)->target();

        $start = CarbonImmutable::now('Asia/Kuala_Lumpur')->startOfMonth();
        $nextStart = $start->addMonth();

        /** @var Collection<int, Partner> $partners */
        $partners = Partner::query()
            ->visibleTo($user)
            ->orderBy('applicant_name')
            ->get(['id', 'applicant_name', 'partner_no']);

        if ($partners->isEmpty()) {
            return [];
        }

        $visibleIds = $partners->pluck('id')->all();

        // ONE grouped query, keyed by partner_id, restricted to the visible partners.
        $counts = Customer::query()
            ->whereIn('partner_id', $visibleIds)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $nextStart)
            ->selectRaw('partner_id, COUNT(*) as aggregate')
            ->groupBy('partner_id')
            ->pluck('aggregate', 'partner_id');

        return $partners->map(function (Partner $partner) use ($counts, $target): array {
            $count = (int) ($counts[$partner->id] ?? 0);
            $percent = $target > 0 ? (int) floor(($count / $target) * 100) : 0;

            return [
                'partner_id' => $partner->id,
                'partner' => $partner->applicant_name,
                'partner_no' => $partner->partner_no,
                'count' => $count,
                'target' => $target,
                'percent' => $percent,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter([
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'partner_id' => $this->partner_id,
            'business_owner_id' => $this->business_owner_id,
            'territory_id' => $this->territory_id,
            'customer_category' => $this->customer_category,
            'product_type_id' => $this->product_type_id,
            'status' => $this->status,
        ], fn ($value): bool => filled($value));
    }

    /**
     * @return array<string, string>
     */
    public function partnerOptions(): array
    {
        return Partner::query()->visibleTo(auth()->user())->orderBy('applicant_name')->pluck('applicant_name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public function businessOwnerOptions(): array
    {
        return BusinessOwner::query()->visibleTo(auth()->user())->orderBy('applicant_name')->pluck('applicant_name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public function territoryOptions(): array
    {
        return Territory::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public function customerCategoryOptions(): array
    {
        return CustomerCategory::options();
    }

    /**
     * @return array<string, string>
     */
    public function productTypeOptions(): array
    {
        return ProductType::query()->orderBy('name')->pluck('name', 'id')->all();
    }
}
