<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class BusinessStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    /**
     * Business-owner dashboard widget only. Guards against the admin panel,
     * which auto-discovers every widget under app/Filament/Widgets.
     */
    public static function canView(): bool
    {
        return auth()->user()?->isBusinessOwner() ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $customers = Customer::query()->visibleTo($user);

        $total = (clone $customers)->count();
        $vip = (clone $customers)->where('is_vip', true)->count();
        $newThisMonth = (clone $customers)
            ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->count();
        $products = Product::query()->visibleTo($user)
            ->where('status', ProductStatus::Active)
            ->count();

        $followUpsDue = CustomerFollowUp::query()->visibleTo($user)
            ->whereNull('completed_at')
            ->whereDate('due_on', '<=', now('Asia/Kuala_Lumpur')->toDateString())
            ->count();

        return [
            Stat::make(__('pios.widgets.followups_due'), $followUpsDue)
                ->description($followUpsDue > 0
                    ? __('pios.widgets.followups_stat_due')
                    : __('pios.widgets.followups_stat_clear'))
                ->descriptionIcon($followUpsDue > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-circle')
                ->color($followUpsDue > 0 ? 'warning' : 'success'),
            Stat::make(__('pios.widgets.total_customers'), $total)
                ->description($vip.' '.__('pios.widgets.vip_short'))
                ->descriptionIcon('heroicon-m-users')
                ->chart($this->dailyCounts($customers))
                ->color('info'),
            Stat::make(__('pios.widgets.new_this_month'), $newThisMonth)
                ->description(__('pios.widgets.new_this_month_hint'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),
            Stat::make(__('pios.widgets.available_products'), $products)
                ->description(__('pios.widgets.available_products_hint'))
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),
        ];
    }

    /**
     * New customers per day for the last 7 days (oldest first), for a sparkline.
     *
     * @param  Builder<Customer>  $query
     * @return array<int, int>
     */
    protected function dailyCounts(Builder $query): array
    {
        $today = CarbonImmutable::now()->startOfDay();

        return collect(range(6, 0))
            ->map(fn (int $daysAgo): int => (clone $query)
                ->whereBetween('created_at', [$today->subDays($daysAgo), $today->subDays($daysAgo)->endOfDay()])
                ->count())
            ->all();
    }
}
