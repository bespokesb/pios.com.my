<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        $bgeProduct = SubscriptionProduct::where('code', 'bge')->first();
        $bgeSubscription = Subscription::query()
            ->when($bo, fn ($q) => $q->where('business_owner_id', $bo->id))
            ->where('subscription_product_id', $bgeProduct?->id)
            ->first();

        if (! $bgeSubscription && $bo) {
            $bgeSubscription = app(SubscriptionService::class)->ensureInitialBgeSubscription($bo);
        }

        $partner = $bo?->partner;

        // WhatsApp renewal URL for Partner
        $partnerPhone = preg_replace('/[^0-9]/', '', $partner?->hp_no ?? '');
        if (str_starts_with($partnerPhone, '0')) {
            $partnerPhone = '60'.substr($partnerPhone, 1);
        }

        $partnerName = $partner?->applicant_name ?: 'Rakan Kongsi';
        $boName = $bo?->applicant_name ?: $user->name;
        $boNo = $bo?->business_owner_no ?: 'BO';

        $renewWaText = "Salam Rakan Kongsi {$partnerName},\n\nSaya {$boName} ({$boNo}) ingin membuat bayaran pembaharuan langganan BizGrowth Engine (BGE) sebanyak RM 30.00 untuk tempoh 30 hari.\n\nSila berikan maklumat akaun bank atau sahkan penerimaan bayaran saya di portal Partner anda untuk mengaktifkan 30 hari langganan. Terima kasih!";

        $partnerWhatsAppRenewUrl = filled($partnerPhone)
            ? 'https://wa.me/'.$partnerPhone.'?text='.rawurlencode($renewWaText)
            : 'https://wa.me/?text='.rawurlencode($renewWaText);

        return view('business.settings', compact('user', 'bo', 'bgeSubscription', 'partner', 'partnerWhatsAppRenewUrl'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null, 403);

        $validated = $request->validate([
            'applicant_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'hp_no' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user->name = $validated['applicant_name'];
        $bo->applicant_name = $validated['applicant_name'];
        $bo->company_name = $validated['company_name'] ?? $bo->company_name;
        $bo->hp_no = $validated['hp_no'] ?? $bo->hp_no;

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_'.$user->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');

            // Delete old avatar if present
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $path;
        }

        $user->save();
        $bo->save();

        return redirect()->route('business.settings.index')->with('success', 'Profil perniagaan dan gambar profil berjaya dikemaskini.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('business.settings.index')->with('success', 'Kata laluan berjaya dikemaskini.');
    }

    public function updateWhatsApp(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null, 403);

        $validated = $request->validate([
            'whatsapp_provider' => ['required', 'string', 'in:manual,fonnte,ultramsg'],
            'whatsapp_api_token' => ['nullable', 'string', 'max:500'],
            'whatsapp_instance_id' => ['nullable', 'string', 'max:100'],
        ]);

        $provider = $validated['whatsapp_provider'];
        $token = filled($validated['whatsapp_api_token'] ?? null) ? trim((string) $validated['whatsapp_api_token']) : null;
        $instanceId = filled($validated['whatsapp_instance_id'] ?? null) ? trim((string) $validated['whatsapp_instance_id']) : null;

        $bo->whatsapp_provider = $provider;
        $bo->whatsapp_api_token = $token;
        $bo->whatsapp_instance_id = $instanceId;

        if ($provider === 'manual') {
            $bo->whatsapp_status = 'connected';
            $bo->whatsapp_last_tested_at = now();
        } else {
            // Auto test connection if credentials provided
            $manager = app(WhatsAppManager::class);
            $testRes = $manager->testConnection($bo, $provider, [
                'token' => $token,
                'instance_id' => $instanceId,
            ]);

            $bo->whatsapp_status = $testRes['success'] ? 'connected' : 'error';
            $bo->whatsapp_last_tested_at = now();
        }

        $bo->save();

        $providerLabel = $bo->whatsAppProviderLabel();
        $msg = "Tetapan WhatsApp Blaster ({$providerLabel}) berjaya disimpan.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'status' => $bo->whatsapp_status,
                'provider' => $bo->whatsapp_provider,
            ]);
        }

        return redirect()->route('business.settings.index')->with('success', $msg);
    }

    public function testWhatsApp(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null, 403);

        $provider = (string) $request->input('whatsapp_provider', $bo->whatsapp_provider ?: 'manual');
        $token = $request->filled('whatsapp_api_token') ? (string) $request->input('whatsapp_api_token') : $bo->whatsapp_api_token;
        $instanceId = $request->filled('whatsapp_instance_id') ? (string) $request->input('whatsapp_instance_id') : $bo->whatsapp_instance_id;
        $testPhone = $request->filled('test_phone') ? (string) $request->input('test_phone') : ($bo->hp_no ?: '60123456789');

        $manager = app(WhatsAppManager::class);

        $pingResult = $manager->testConnection($bo, $provider, [
            'token' => $token,
            'instance_id' => $instanceId,
        ]);

        if (! $pingResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $pingResult['message'],
            ], 422);
        }

        // If user wants to send a live test message to a phone number:
        if ($request->boolean('send_test_message') && $provider !== 'manual') {
            $driver = $manager->driver($provider);
            $testMsg = "Salam! Ini adalah mesej ujian sambungan WhatsApp Blaster PIOS BizGrowth Engine untuk perniagaan {$bo->applicant_name}. Sambungan berjaya!";
            $sendRes = $driver->sendMessage($testPhone, $testMsg, [
                'token' => $token,
                'instance_id' => $instanceId,
            ]);

            if (! $sendRes['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ujian ping berjaya tetapi mesej ujian gagal dihantar: '.$sendRes['message'],
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $pingResult['message'],
            'device' => $pingResult['device'] ?? null,
        ]);
    }

    public function blastProduct(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null && $product->business_owner_id === $bo->id, 403);

        $targetAudience = $request->input('target', 'all'); // 'all', 'selected', 'custom'
        $customPhone = $request->input('custom_phone');
        $selectedIds = (array) $request->input('customer_ids', []);
        $customText = $request->input('message');

        $manager = app(WhatsAppManager::class);

        // Build product promo text if not customized
        $promoText = filled($customText) ? $customText : (
            "Salam {name},\n\n"
            ."Tawaran Istimewa Produk: *{$product->name}*\n"
            .'Harga: *RM '.number_format((float) $product->selling_price, 2)."*\n"
            .($product->description ? ($product->description."\n\n") : "\n")
            ."Klik pautan untuk buat pesanan terus:\n"
            .route('public.product.show', $product->product_no)
        );

        $imageUrl = $product->firstShareableImageUrl();

        $targets = [];
        if ($targetAudience === 'custom' && filled($customPhone)) {
            $targets[] = ['phone' => $customPhone, 'name' => 'Pelanggan'];
        } elseif ($targetAudience === 'selected' && ! empty($selectedIds)) {
            $customers = Customer::query()->where('business_owner_id', $bo->id)->whereIn('id', $selectedIds)->get();
            foreach ($customers as $c) {
                if ($c->hp_no) {
                    $targets[] = ['phone' => $c->hp_no, 'name' => $c->name];
                }
            }
        } else {
            // All active customers
            $customers = Customer::query()->where('business_owner_id', $bo->id)->get();
            foreach ($customers as $c) {
                if ($c->hp_no) {
                    $targets[] = ['phone' => $c->hp_no, 'name' => $c->name];
                }
            }
        }

        if (empty($targets)) {
            return response()->json([
                'success' => false,
                'message' => 'Tiada penerima telefon WhatsApp ditemui untuk dihantar.',
            ], 422);
        }

        $result = $manager->sendBlast($bo, $targets, $promoText, $imageUrl);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['is_manual']
                    ? "Pautan manual WhatsApp sedia untuk {$result['total']} pelanggan."
                    : "Blast selesai: {$result['sent']} berjaya dihantar, {$result['failed']} gagal.",
                'result' => $result,
            ]);
        }

        return redirect()->back()->with('success', "Blast WhatsApp selesai untuk {$result['sent']} pelanggan.");
    }
}
