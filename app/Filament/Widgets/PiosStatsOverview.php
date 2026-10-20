<?php

namespace App\Filament\Widgets;

use App\Enums\FeeStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductProposalStatus;
use App\Enums\PromotionStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Fee;
use App\Models\Order;
use App\Models\Partner;
use App\Models\ProductProposal;
use App\Models\Promotion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PiosStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $stats = [];

        // Network reach — only relevant to roles that sit above Business Owners.
        if ($user->isAdmin()) {
            $stats[] = Stat::make(__('pios.widgets.partners'), Partner::query()->visibleTo($user)->count())
                ->description(__('pios.widgets.partners_hint'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary');
        }

        if ($user->isAdmin() || $user->isPartner()) {
            $stats[] = Stat::make(__('pios.widgets.business_owners'), BusinessOwner::query()->visibleTo($user)->count())
                ->description(__('pios.widgets.business_owners_hint'))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary');
        }

        // Customers — with VIP breakdown and a 7-day acquisition sparkline.
        $customers = Customer::query()->visibleTo($user);
        $vipCount = (clone $customers)->where('is_vip', true)->count();

        $stats[] = Stat::make(__('pios.widgets.customers'), (clone $customers)->count())
            ->description(trans_choice('pios.widgets.vip_customers', $vipCount, ['count' => $vipCount]))
            ->descriptionIcon('heroicon-m-star')
            ->chart($this->dailyCounts(Customer::query()->visibleTo($user)))
            ->color('info');

        // Revenue from settled orders.
        $revenue = (float) Order::query()->visibleTo($user)
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
            ->sum('total');

        $stats[] = Stat::make(__('pios.widgets.revenue'), 'RM '.number_format($revenue, 2))
            ->description(__('pios.widgets.revenue_hint'))
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('success');

        // Outstanding fees that still need to be collected.
        $outstanding = (float) Fee::query()->visibleTo($user)
            ->whereIn('status', [FeeStatus::Unpaid, FeeStatus::Pending, FeeStatus::Overdue])
            ->sum('amount');

        $stats[] = Stat::make(__('pios.widgets.outstanding_fees'), 'RM '.number_format($outstanding, 2))
            ->description(__('pios.widgets.outstanding_fees_hint'))
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color($outstanding > 0 ? 'warning' : 'success');

        // Active promotions in flight.
        $activePromotions = Promotion::query()->visibleTo($user)
            ->whereIn('status', [PromotionStatus::Running, PromotionStatus::Scheduled])
            ->count();

        $stats[] = Stat::make(__('pios.widgets.active_promotions'), $activePromotions)
            ->description(__('pios.widgets.active_promotions_hint'))
            ->descriptionIcon('heroicon-m-megaphone')
            ->color('primary');

        // Product proposals awaiting a decision.
        $pendingProposals = ProductProposal::query()->visibleTo($user)
            ->whereIn('approval_status', [ProductProposalStatus::Pending, ProductProposalStatus::Submitted, ProductProposalStatus::UnderReview])
            ->count();

        $stats[] = Stat::make(__('pios.widgets.pending_proposals'), $pendingProposals)
            ->description(__('pios.widgets.pending_proposals_hint'))
            ->descriptionIcon('heroicon-m-clipboard-document-check')
            ->color($pendingProposals > 0 ? 'warning' : 'gray');

        return $stats;
    }

    /**
     * Count of new records per day for the last 7 days (oldest first), for a sparkline.
     *
     * @param  Builder<Model>  $query
     * @return array<int, int>
     */
    protected function dailyCounts($query): array
    {
        $today = CarbonImmutable::now()->startOfDay();

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($query, $today): int {
                $day = $today->subDays($daysAgo);

                return (clone $query)
                    ->whereBetween('created_at', [$day, $day->endOfDay()])
                    ->count();
            })
            ->all();
    }
}
