<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Business-owner order dashboard (DP-17): pipeline counts and revenue over the
 * BO's OWN orders only (Order::scopeVisibleTo). Revenue counts confirmed sales
 * — Paid onward — and never a Submitted (unpaid) or Cancelled order.
 */
class OrderStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /** The statuses that represent confirmed (paid) revenue. */
    private const REVENUE_STATUSES = [
        OrderStatus::Paid,
        OrderStatus::Packed,
        OrderStatus::Shipped,
        OrderStatus::Completed,
    ];

    /**
     * Business-owner dashboard widget only (the admin panel auto-discovers every
     * widget under app/Filament/Widgets).
     */
    public static function canView(): bool
    {
        // Business panel only — on /admin a dual-role admin+BO would otherwise see
        // platform-wide totals (admin scopeVisibleTo sees all) under a "your
        // orders" label. Matches BusinessSalesReport::canAccess().
        return Filament::getCurrentPanel()?->getId() === 'business'
            && (auth()->user()?->isBusinessOwner() ?? false);
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $orders = fn () => Order::query()->visibleTo($user);

        $total = $orders()->count();
        $awaiting = $orders()->where('status', OrderStatus::Submitted)->count();
        $inFulfilment = $orders()->whereIn('status', [OrderStatus::Paid, OrderStatus::Packed, OrderStatus::Shipped])->count();
        $completed = $orders()->where('status', OrderStatus::Completed)->count();

        $revenue = (float) $orders()->whereIn('status', self::REVENUE_STATUSES)->sum('total');
        $revenueThisMonth = (float) $orders()
            ->whereIn('status', self::REVENUE_STATUSES)
            ->where('ordered_at', '>=', CarbonImmutable::now('Asia/Kuala_Lumpur')->startOfMonth())
            ->sum('total');

        return [
            Stat::make(__('pios.widgets.orders_total'), $total)
                ->description(__('pios.widgets.orders_completed_hint', ['count' => $completed]))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),
            Stat::make(__('pios.widgets.orders_revenue'), 'RM '.number_format($revenue, 2))
                ->description(__('pios.widgets.orders_revenue_hint', ['amount' => number_format($revenueThisMonth, 2)]))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make(__('pios.widgets.orders_awaiting'), $awaiting)
                ->description(__('pios.widgets.orders_awaiting_hint'))
                ->descriptionIcon($awaiting > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-circle')
                ->color($awaiting > 0 ? 'warning' : 'success'),
            Stat::make(__('pios.widgets.orders_in_fulfilment'), $inFulfilment)
                ->description(__('pios.widgets.orders_in_fulfilment_hint'))
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
        ];
    }
}
