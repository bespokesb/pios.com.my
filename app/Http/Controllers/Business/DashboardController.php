<?php

namespace App\Http\Controllers\Business;

use App\Enums\CustomerCategory;
use App\Enums\OrderStatus;
use App\Enums\PromotionStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        // KPI Counts
        $customerQuery = Customer::query()->visibleTo($user);
        if (! $user->isAdmin() && $bo) {
            $customerQuery->where('business_owner_id', $bo->id);
        }
        $totalCustomers = (clone $customerQuery)->count();
        $leadCount = (clone $customerQuery)->where('category', CustomerCategory::Lead)->count();
        $prospectsCount = (clone $customerQuery)->where('category', CustomerCategory::Prospect)->count();
        $standardCustomersCount = (clone $customerQuery)->where('category', CustomerCategory::Customer)->count();
        $loyalCustomersCount = (clone $customerQuery)->where('category', CustomerCategory::LoyalCustomer)->count();
        $membersCount = (clone $customerQuery)->where('category', CustomerCategory::Member)->count();
        $partnersCount = (clone $customerQuery)->where('category', CustomerCategory::Partner)->count();
        $vipCount = (clone $customerQuery)->where('is_vip', true)->count();

        $productQuery = Product::query()->visibleTo($user);
        if (! $user->isAdmin() && $bo) {
            $productQuery->where('business_owner_id', $bo->id);
        }
        $productsCount = (clone $productQuery)->count();

        $promotionsCount = Promotion::query()
            ->when($bo, fn ($q) => $q->where('business_owner_id', $bo->id))
            ->where('status', PromotionStatus::Running)
            ->count();

        $openOrdersQuery = Order::query()->visibleTo($user)
            ->whereIn('status', [OrderStatus::Submitted, OrderStatus::Paid, OrderStatus::Packed]);
        if (! $user->isAdmin() && $bo) {
            $openOrdersQuery->where('business_owner_id', $bo->id);
        }
        $openOrdersCount = $openOrdersQuery->count();

        // Customer Pipeline breakdown (matching all 6 menu categories + VIP)
        $pipelineTotal = max($totalCustomers, 1);
        $pipeline = [
            [
                'stage' => 'Lead',
                'filter' => 'lead',
                'count' => $leadCount,
                'pct' => round(($leadCount / $pipelineTotal) * 100, 1),
                'color' => 'slate',
                'badge' => 'Lead',
            ],
            [
                'stage' => 'Prospek',
                'filter' => 'prospect',
                'count' => $prospectsCount,
                'pct' => round(($prospectsCount / $pipelineTotal) * 100, 1),
                'color' => 'blue',
                'badge' => 'Prospek',
            ],
            [
                'stage' => 'Customer',
                'filter' => 'customer',
                'count' => $standardCustomersCount,
                'pct' => round(($standardCustomersCount / $pipelineTotal) * 100, 1),
                'color' => 'teal',
                'badge' => 'Customer',
            ],
            [
                'stage' => 'Loyal Customer',
                'filter' => 'loyal_customer',
                'count' => $loyalCustomersCount,
                'pct' => round(($loyalCustomersCount / $pipelineTotal) * 100, 1),
                'color' => 'pink',
                'badge' => 'Loyal Customer',
            ],
            [
                'stage' => 'Member',
                'filter' => 'member',
                'count' => $membersCount,
                'pct' => round(($membersCount / $pipelineTotal) * 100, 1),
                'color' => 'purple',
                'badge' => 'Member',
            ],
            [
                'stage' => 'Partner',
                'filter' => 'partner',
                'count' => $partnersCount,
                'pct' => round(($partnersCount / $pipelineTotal) * 100, 1),
                'color' => 'orange',
                'badge' => 'Partner',
            ],
            [
                'stage' => 'VIP',
                'filter' => 'vip',
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

        return view('business.dashboard-page', compact(
            'user',
            'bo',
            'totalCustomers',
            'prospectsCount',
            'vipCount',
            'productsCount',
            'promotionsCount',
            'openOrdersCount',
            'pipeline',
            'recentActivities'
        ));
    }
}
