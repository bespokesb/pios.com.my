<?php

namespace App\Http\Controllers\Business;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $period = $request->input('period', 'month');

        $baseOrders = Order::query()->visibleTo($user)
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Completed]);

        $totalRevenue = (clone $baseOrders)->sum('total');
        $totalOrders = (clone $baseOrders)->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $totalCustomers = Customer::query()->visibleTo($user)->count();
        $newCustomersThisMonth = Customer::query()->visibleTo($user)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        // Top products by sales volume
        $topProducts = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->visibleTo($user)->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Completed]))
            ->with('product')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(line_total) as total_sales'))
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        return view('business.reports', compact(
            'period',
            'totalRevenue',
            'totalOrders',
            'avgOrderValue',
            'totalCustomers',
            'newCustomersThisMonth',
            'topProducts'
        ));
    }
}
