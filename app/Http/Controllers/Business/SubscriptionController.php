<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
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
            ->when($bo, fn ($q) => $q->where('business_owner_id', $bo->id))
            ->where('subscription_product_id', $bgeProduct?->id)
            ->first();

        if (! $bgeSubscription && $bo) {
            $bgeSubscription = $this->subscriptionService->ensureInitialBgeSubscription($bo);
        }

        // All subscriptions for this user
        $subscriptions = Subscription::query()
            ->when($bo, fn ($q) => $q->where('business_owner_id', $bo->id))
            ->with(['product', 'latestPayment'])
            ->get();

        // All payment transactions submitted by this Business Owner
        $payments = SubscriptionPayment::query()
            ->when($bo, fn ($q) => $q->where('business_owner_id', $bo->id))
            ->with(['product', 'approver'])
            ->latest()
            ->get();

        // Sponsoring partner info
        $partner = $bo?->partner;

        // Partner WhatsApp renewal link
        $partnerPhone = preg_replace('/[^0-9]/', '', $partner?->hp_no ?? '');
        if (str_starts_with($partnerPhone, '0')) {
            $partnerPhone = '60'.substr($partnerPhone, 1);
        }

        $partnerName = $partner?->applicant_name ?: 'Partner';
        $boName = $bo?->applicant_name ?: $user->name;
        $boNo = $bo?->business_owner_no ?: 'BO';

        $renewWaText = "Hello Partner {$partnerName},\n\nI am {$boName} ({$boNo}) and would like to renew my BizGrowth Engine (BGE) subscription (RM 30.00 / 30 Days).\n\nPlease provide your payment details (Cash or Bank Transfer) or verify my payment in your Partner Portal. Thank you!";

        $partnerWhatsAppRenewUrl = filled($partnerPhone)
            ? 'https://wa.me/'.$partnerPhone.'?text='.rawurlencode($renewWaText)
            : 'https://wa.me/?text='.rawurlencode($renewWaText);

        return view('business.subscriptions', compact(
            'products',
            'bgeProduct',
            'bgeSubscription',
            'subscriptions',
            'payments',
            'partner',
            'bo',
            'partnerWhatsAppRenewUrl'
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

        $message = "Subscription request for '{$product->name}' (Payment No: {$payment->payment_no}) successfully submitted. Please await confirmation from your Partner.";

        return redirect()->route('business.subscriptions.index')
            ->with('success', $message);
    }

    public function renewViaWhatsApp(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null, 403);

        $bgeProduct = SubscriptionProduct::where('code', 'bge')->firstOrFail();
        $partner = $bo->partner;

        // Auto create or reuse pending payment record so partner has it in their Filament queue to approve
        $existingPending = SubscriptionPayment::where('business_owner_id', $bo->id)
            ->where('subscription_product_id', $bgeProduct->id)
            ->where('status', 'pending_approval')
            ->first();

        if (! $existingPending) {
            $payment = $this->subscriptionService->submitPayment($user, $bgeProduct, [
                'payment_method' => 'cash',
                'notes' => 'Subscription renewal request via WhatsApp Partner.',
            ]);
        } else {
            $payment = $existingPending;
        }

        // Generate WhatsApp link with customized payment text
        $phone = preg_replace('/[^0-9]/', '', $partner?->hp_no ?? '');
        if (str_starts_with($phone, '0')) {
            $phone = '60'.substr($phone, 1);
        }

        $partnerName = $partner?->applicant_name ?: 'Partner';
        $boName = $bo->applicant_name ?: $user->name;
        $boNo = $bo->business_owner_no ?: 'BO';

        $text = "Hello Partner {$partnerName},\n\nI am {$boName} ({$boNo}) and would like to renew my BizGrowth Engine (BGE) subscription (RM 30.00 / 30 Days).\n\nPayment Ref: {$payment->payment_no}\n\nPlease share your payment details (Cash or Bank Transfer) or confirm receipt of my payment in your Partner Portal. Thank you!";

        $whatsappUrl = filled($phone)
            ? 'https://wa.me/'.$phone.'?text='.rawurlencode($text)
            : 'https://wa.me/?text='.rawurlencode($text);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'whatsapp_url' => $whatsappUrl,
                'payment_no' => $payment->payment_no,
                'message' => 'Subscription renewal request recorded. Please continue your WhatsApp conversation with your Partner.',
            ]);
        }

        return redirect()->away($whatsappUrl);
    }
}
