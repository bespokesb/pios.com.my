<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicOrderRequest;
use App\Models\Product;
use App\Services\ManualOrderService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customer-facing, unauthenticated product pages. Only Active, Business-Owner-
 * owned products are reachable; cost/vendor/margin are never exposed because the
 * views read only the safe presentation fields (name, price, description, media).
 */
class PublicProductController extends Controller
{
    public function show(Product $product): Response
    {
        abort_unless($product->isPubliclyVisible(), 404);

        $businessOwner = $product->businessOwner;

        return response()->view('public.product', [
            'product' => $product,
            'businessOwner' => $businessOwner,
            'images' => $product->imageUrls(),
            'videoUrl' => $product->videoUrl(),
            'orderable' => $product->isPubliclyOrderable(),
            'sellerWhatsApp' => Phone::toWhatsApp($businessOwner?->hp_no),
        ])
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    public function order(
        StorePublicOrderRequest $request,
        Product $product,
        ManualOrderService $orders,
    ): RedirectResponse {
        abort_unless($product->isPubliclyVisible(), 404);
        // Amway (and any non-orderable) products never accept a public order.
        abort_unless($product->isPubliclyOrderable(), 403);

        $order = $orders->placePublicOrder($product, $request->validated());

        return redirect()
            ->route('public.product.show', ['product' => $product->product_no])
            ->with('order_confirmation', [
                'order_no' => $order->order_no,
                'seller_whatsapp' => Phone::toWhatsApp($product->businessOwner?->hp_no),
            ]);
    }
}
