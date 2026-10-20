<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Business-owner sales report (DP-18): daily and weekly orders + revenue over the
 * BO's OWN orders (Order::scopeVisibleTo), in Asia/Kuala_Lumpur. Revenue counts
 * confirmed sales — Paid onward — never a Submitted (unpaid) or Cancelled order.
 */
class BusinessSalesReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected string $view = 'filament.pages.business-sales-report';

    private const TZ = 'Asia/Kuala_Lumpur';

    /** The statuses that count as confirmed (paid) revenue. */
    private const REVENUE_STATUSES = [
        OrderStatus::Paid,
        OrderStatus::Packed,
        OrderStatus::Shipped,
        OrderStatus::Completed,
    ];

    public string $period = 'daily';

    public ?string $date = null;

    public function mount(): void
    {
        $this->date ??= CarbonImmutable::now(self::TZ)->toDateString();
    }

    /** Business panel only — the admin panel has its own OperationalReports. */
    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'business'
            && (auth()->user()?->isBusinessOwner() ?? false);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('pios.report.sales.nav');
    }

    public function getTitle(): string
    {
        return __('pios.report.sales.title');
    }

    /**
     * The report data for the current period/date.
     *
     * @return array{period: string, label: string, orders: int, revenue: float, rows: array<int, array{label: string, orders: int, revenue: float}>}
     */
    public function getReportProperty(): array
    {
        $anchor = CarbonImmutable::parse($this->date ?: 'today', self::TZ);

        return $this->period === 'weekly'
            ? $this->weekly($anchor)
            : $this->daily($anchor);
    }

    /**
     * @return array{period: string, label: string, orders: int, revenue: float, rows: array<int, array{label: string, orders: int, revenue: float}>}
     */
    private function daily(CarbonImmutable $day): array
    {
        [$orders, $revenue] = $this->totalsFor($day->startOfDay(), $day->endOfDay());

        return [
            'period' => 'daily',
            'label' => $day->translatedFormat('l, d M Y'),
            'orders' => $orders,
            'revenue' => $revenue,
            'rows' => [],
        ];
    }

    /**
     * @return array{period: string, label: string, orders: int, revenue: float, rows: array<int, array{label: string, orders: int, revenue: float}>}
     */
    private function weekly(CarbonImmutable $anchor): array
    {
        $start = $anchor->startOfWeek();
        $rows = [];
        $totalOrders = 0;
        $totalRevenue = 0.0;

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            [$orders, $revenue] = $this->totalsFor($day->startOfDay(), $day->endOfDay());
            $rows[] = ['label' => $day->translatedFormat('D, d M'), 'orders' => $orders, 'revenue' => $revenue];
            $totalOrders += $orders;
            $totalRevenue += $revenue;
        }

        return [
            'period' => 'weekly',
            'label' => $start->translatedFormat('d M').' – '.$start->addDays(6)->translatedFormat('d M Y'),
            'orders' => $totalOrders,
            'revenue' => $totalRevenue,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{0: int, 1: float}
     */
    private function totalsFor(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [0, 0.0];
        }

        $base = fn (): Builder => Order::query()
            ->visibleTo($user)
            ->whereBetween('ordered_at', [$from, $to]);

        $orders = $base()->count();
        $revenue = (float) $base()->whereIn('status', self::REVENUE_STATUSES)->sum('total');

        return [$orders, $revenue];
    }
}
