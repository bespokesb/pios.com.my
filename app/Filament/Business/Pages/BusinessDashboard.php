<?php

namespace App\Filament\Business\Pages;

use App\Enums\CustomerCategory;
use App\Enums\OrderStatus;
use App\Enums\PromotionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class BusinessDashboard extends BaseDashboard
{
    protected static string $layout = 'layouts.business';

    protected string $view = 'business.dashboard';

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'business'
            && (auth()->user()?->isBusinessOwner() ?? false);
    }

    public static function getRelativeRouteName(?Panel $panel = null): string
    {
        return 'dashboard';
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        $panel = $panel ?? Filament::getCurrentPanel() ?? Filament::getPanel('business');

        return $panel->generateRouteName('pages.dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard';
    }

    public function getViewData(): array
    {
        /** @var User|null $user */
        $user = auth()->user();
        $bo = $user?->businessOwner;

        if (! $user) {
            return [
                'user' => null,
                'bo' => null,
                'totalCustomers' => 0,
                'prospectsCount' => 0,
                'vipCount' => 0,
                'productsCount' => 0,
                'promotionsCount' => 0,
                'openOrdersCount' => 0,
                'pipeline' => [],
                'recentActivities' => [],
            ];
        }

        // KPI Counts
        $customerQuery = Customer::query()->visibleTo($user);
        $totalCustomers = (clone $customerQuery)->count();
        $prospectsCount = (clone $customerQuery)->where('category', CustomerCategory::Prospect)->count();
        $vipCount = (clone $customerQuery)->where('is_vip', true)->count();
        $contactsCount = (clone $customerQuery)->where('category', CustomerCategory::Lead)->count();
        $standardCustomersCount = (clone $customerQuery)->where('category', CustomerCategory::Customer)->count();

        $productQuery = Product::query()->visibleTo($user);
        $productsCount = (clone $productQuery)->count();

        $promotionsCount = Promotion::query()
            ->when($bo, fn ($q) => $q->where('business_owner_id', $bo->id))
            ->where('status', PromotionStatus::Running)
            ->count();

        $openOrdersCount = Order::query()->visibleTo($user)
            ->whereIn('status', [OrderStatus::Submitted, OrderStatus::Paid, OrderStatus::Packed])
            ->count();

        // Customer Pipeline breakdown
        $pipelineTotal = max($totalCustomers, 1);
        $pipeline = [
            [
                'stage' => 'Contact',
                'count' => $contactsCount,
                'pct' => round(($contactsCount / $pipelineTotal) * 100, 1),
                'color' => 'slate',
                'badge' => 'Contact',
            ],
            [
                'stage' => 'Prospect',
                'count' => $prospectsCount,
                'pct' => round(($prospectsCount / $pipelineTotal) * 100, 1),
                'color' => 'blue',
                'badge' => 'Prospect',
            ],
            [
                'stage' => 'Customer',
                'count' => $standardCustomersCount,
                'pct' => round(($standardCustomersCount / $pipelineTotal) * 100, 1),
                'color' => 'teal',
                'badge' => 'Customer',
            ],
            [
                'stage' => 'VIP',
                'count' => $vipCount,
                'pct' => round(($vipCount / $pipelineTotal) * 100, 1),
                'color' => 'yellow',
                'badge' => 'VIP',
            ],
        ];

        // Recent Orders as activities
        $recentOrders = Order::query()->visibleTo($user)
            ->with(['customer', 'items.product'])
            ->latest()
            ->take(8)
            ->get();

        $recentActivities = $recentOrders->map(function (Order $order): array {
            $customerName = $order->customer?->name ?? 'Customer';
            $firstItem = $order->items->first()?->product?->name ?? 'Items';
            $totalFormatted = 'RM '.number_format((float) $order->total, 2);

            return [
                'time' => $order->created_at->format('h:i A'),
                'date' => $order->created_at->format('M d'),
                'title' => "Order #{$order->order_no}",
                'desc' => "{$customerName} — {$firstItem} ({$totalFormatted})",
                'badge' => $order->status->value,
                'badge_class' => match ($order->status) {
                    OrderStatus::Paid => 'badge-paid',
                    OrderStatus::Shipped, OrderStatus::Completed => 'badge-active',
                    OrderStatus::Cancelled => 'badge-danger',
                    default => 'badge-order',
                },
            ];
        })->all();

        return [
            'user' => $user,
            'bo' => $bo,
            'totalCustomers' => $totalCustomers,
            'prospectsCount' => $prospectsCount,
            'vipCount' => $vipCount,
            'productsCount' => $productsCount,
            'promotionsCount' => $promotionsCount,
            'openOrdersCount' => $openOrdersCount,
            'pipeline' => $pipeline,
            'recentActivities' => $recentActivities,
        ];
    }
}
