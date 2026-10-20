<?php

namespace App\Http\Controllers\Business;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $query = Order::query()->visibleTo($user)->with(['customer', 'items.product', 'payments']);

        if (filled($search)) {
            $query->where(function ($q) use ($search): void {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('customer_no', 'like', "%{$search}%"));
            });
        }

        if ($status !== 'all' && filled($status)) {
            $enumStatus = OrderStatus::tryFrom($status);
            if ($enumStatus) {
                $query->where('status', $enumStatus);
            }
        }

        $orders = $query->latest()->get();

        // Metrics
        $baseQuery = Order::query()->visibleTo($user);
        $totalOrdersCount = (clone $baseQuery)->count();
        $totalRevenue = (clone $baseQuery)->whereIn('status', [OrderStatus::Paid, OrderStatus::Packed, OrderStatus::Shipped, OrderStatus::Completed])->sum('total');
        $paidCount = (clone $baseQuery)->whereIn('status', [OrderStatus::Paid, OrderStatus::Packed])->count();
        $pendingCount = (clone $baseQuery)->whereIn('status', [OrderStatus::Draft, OrderStatus::Submitted])->count();
        $shippedCount = (clone $baseQuery)->where('status', OrderStatus::Shipped)->count();
        $completedCount = (clone $baseQuery)->where('status', OrderStatus::Completed)->count();

        return view('business.transaction', compact(
            'orders',
            'status',
            'search',
            'totalOrdersCount',
            'totalRevenue',
            'paidCount',
            'pendingCount',
            'shippedCount',
            'completedCount'
        ));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin() && $order->business_owner_id !== $user->businessOwner?->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['nullable', 'string', 'max:50'],
            'delivery_provider' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
        ]);

        $newStatus = OrderStatus::tryFrom($validated['status']);
        if (! $newStatus) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Invalid order status.'], 422);
            }

            return back()->withErrors(['status' => 'Invalid order status.']);
        }

        if ($order->status->isTerminal()) {
            $msg = "Order #{$order->order_no} is already {$order->status->label()} and cannot be changed.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->withErrors(['status' => $msg]);
        }

        $canDirectlyTransition = $order->status->canTransitionTo($newStatus);
        $canShortcutTransition = ($order->status === OrderStatus::Paid && $newStatus === OrderStatus::Shipped)
            || ($order->status === OrderStatus::Draft && $newStatus === OrderStatus::Paid);

        if (! $canDirectlyTransition && ! $canShortcutTransition) {
            $msg = "Cannot move order #{$order->order_no} from {$order->status->label()} to {$newStatus->label()}.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->withErrors(['status' => $msg]);
        }

        $provider = trim((string) ($validated['delivery_provider'] ?? ''));
        $rawTracking = trim((string) ($validated['tracking_number'] ?? ''));

        if ($provider !== '' && $rawTracking !== '') {
            if (! str_starts_with(strtolower($rawTracking), strtolower($provider))) {
                $trackingNumber = "{$provider} - {$rawTracking}";
            } else {
                $trackingNumber = $rawTracking;
            }
        } elseif ($rawTracking !== '') {
            $trackingNumber = $rawTracking;
        } elseif ($provider !== '') {
            $trackingNumber = $provider;
        } else {
            $trackingNumber = $order->tracking_number ?? '';
        }

        if ($newStatus === OrderStatus::Shipped && blank($trackingNumber)) {
            $msg = "Shipping order #{$order->order_no} requires courier and tracking number.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->withErrors(['tracking_number' => $msg]);
        }

        try {
            $old = $order->getOriginal();

            if ($order->status === OrderStatus::Paid && $newStatus === OrderStatus::Shipped) {
                $order->transitionTo(OrderStatus::Packed);
                $order->transitionTo(OrderStatus::Shipped, $trackingNumber);
            } elseif ($order->status === OrderStatus::Draft && $newStatus === OrderStatus::Paid) {
                $order->transitionTo(OrderStatus::Submitted);
                $order->transitionTo(OrderStatus::Paid);
            } else {
                $order->transitionTo($newStatus, $trackingNumber ?: null);
            }

            // Record payment reference if provided when marked Paid
            if ($newStatus === OrderStatus::Paid && filled($validated['reference_no'] ?? null)) {
                $refNo = trim((string) $validated['reference_no']);
                if ($refNo !== '') {
                    Payment::firstOrCreate(
                        [
                            'order_id' => $order->id,
                            'reference_no' => $refNo,
                        ],
                        [
                            'amount' => $order->total,
                            'payment_type' => $validated['payment_type'] ?? 'bank_transfer',
                            'status' => PaymentStatus::Verified,
                            'paid_at' => now(),
                            'recorded_by' => $user->id,
                        ]
                    );
                }
            }

            app(AuditLogger::class)->log(
                actor: $user,
                event: 'order_status_updated',
                auditable: $order,
                oldValues: $old,
                newValues: $order->fresh()->getAttributes(),
            );
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['status' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            $baseQuery = Order::query()->visibleTo($user);
            $totalRevenue = (clone $baseQuery)->whereIn('status', [OrderStatus::Paid, OrderStatus::Packed, OrderStatus::Shipped, OrderStatus::Completed])->sum('total');
            $paidCount = (clone $baseQuery)->whereIn('status', [OrderStatus::Paid, OrderStatus::Packed])->count();
            $pendingCount = (clone $baseQuery)->whereIn('status', [OrderStatus::Draft, OrderStatus::Submitted])->count();
            $shippedCount = (clone $baseQuery)->where('status', OrderStatus::Shipped)->count();
            $completedCount = (clone $baseQuery)->where('status', OrderStatus::Completed)->count();

            return response()->json([
                'success' => true,
                'message' => "Order #{$order->order_no} status updated to {$newStatus->label()}.",
                'order' => [
                    'id' => $order->id,
                    'order_no' => $order->order_no,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'tracking_number' => $order->tracking_number,
                    'is_terminal' => $order->status->isTerminal(),
                    'next_statuses' => collect($order->status->nextStatuses())->map(fn (OrderStatus $s): array => [
                        'value' => $s->value,
                        'label' => $s->label(),
                    ])->values()->all(),
                    'paid_at' => $order->paid_at?->format('d M Y, h:i A'),
                    'shipped_at' => $order->shipped_at?->format('d M Y, h:i A'),
                ],
                'metrics' => [
                    'totalRevenue' => number_format((float) $totalRevenue, 2),
                    'paidCount' => $paidCount,
                    'pendingCount' => $pendingCount,
                    'shippedCount' => $shippedCount,
                    'completedCount' => $completedCount,
                ],
            ]);
        }

        return redirect()->route('business.transactions.index')
            ->with('success', "Order #{$order->order_no} status updated to {$newStatus->label()}.");
    }
}
