<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        // Active subscription products available for Business Owners (BGE, POS, etc.)
        $products = SubscriptionProduct::query()
            ->active()
            ->forRole('business_owner')
            ->orderBy('sort_order')
            ->get();

        $bgeProduct = SubscriptionProduct::where('code', 'bge')->first();

        // User's active or latest subscription to BGE
        $bgeSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('subscription_product_id', $bgeProduct?->id)
            ->first();

        // All subscriptions for this user
        $subscriptions = Subscription::query()
            ->where('user_id', $user->id)
            ->with(['product', 'latestPayment'])
            ->get();

        // All payment transactions submitted by this Business Owner
        $payments = SubscriptionPayment::query()
            ->where('user_id', $user->id)
            ->with(['product', 'approver'])
            ->latest()
            ->get();

        // Sponsoring partner info
        $partner = $bo?->partner;

        return view('business.subscriptions', compact(
            'products',
            'bgeProduct',
            'bgeSubscription',
            'subscriptions',
            'payments',
            'partner',
            'bo'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'subscription_product_id' => ['required', 'exists:subscription_products,id'],
            'payment_method' => ['required', 'string', 'in:qr_code,cash,bank_transfer'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = SubscriptionProduct::findOrFail($validated['subscription_product_id']);

        $payment = $this->subscriptionService->submitPayment($user, $product, [
            'payment_method' => $validated['payment_method'],
            'payment_proof' => $request->file('payment_proof'),
            'reference_no' => $validated['reference_no'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $message = "Permohonan langganan '{$product->name}' (No. Bayaran: {$payment->payment_no}) berjaya dihantar. Sila tunggu pengesahan daripada Rakan Kongsi anda atau Pihak Pengurusan PIOS.";

        return redirect()->route('business.subscriptions.index')
            ->with('success', $message);
    }
}
