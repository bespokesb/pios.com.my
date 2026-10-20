<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Printable invoice / receipt documents for an order (DP-16). Rendered as plain
 * HTML the seller prints or saves as PDF from the browser — no PDF dependency.
 * Access is the same BO-ownership invariant as everywhere else: the order must
 * be visible to the acting user (Order::scopeVisibleTo), never another BO's.
 */
class OrderDocumentController extends Controller
{
    public function invoice(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        // An invoice exists once the order has been submitted (not while Draft).
        abort_if($order->status === OrderStatus::Draft, 404);

        return view('documents.order', $this->documentData($order, 'invoice'));
    }

    public function receipt(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        // A receipt exists only once the order has been paid.
        abort_if($order->paid_at === null, 404);

        return view('documents.order', $this->documentData($order, 'receipt'));
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && Order::query()->visibleTo($user)->whereKey($order->getKey())->exists(),
            403,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function documentData(Order $order, string $variant): array
    {
        $order->loadMissing(['items', 'customer', 'businessOwner']);

        return [
            'variant' => $variant,
            'order' => $order,
            'seller' => $order->businessOwner,
            'customer' => $order->customer,
            'items' => $order->items,
        ];
    }
}
