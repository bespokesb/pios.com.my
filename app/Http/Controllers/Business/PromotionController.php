<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null, 403);

        $products = Product::query()
            ->where('business_owner_id', $bo->id)
            ->latest()
            ->get();

        $customers = Customer::query()
            ->where('business_owner_id', $bo->id)
            ->orderBy('name')
            ->get();

        $campaigns = WhatsAppCampaign::query()
            ->where('business_owner_id', $bo->id)
            ->with('product')
            ->latest()
            ->paginate(12);

        $stats = [
            'total_campaigns' => WhatsAppCampaign::query()->where('business_owner_id', $bo->id)->count(),
            'scheduled_count' => WhatsAppCampaign::query()->where('business_owner_id', $bo->id)->where('status', WhatsAppCampaign::STATUS_SCHEDULED)->count(),
            'completed_count' => WhatsAppCampaign::query()->where('business_owner_id', $bo->id)->whereIn('status', [WhatsAppCampaign::STATUS_COMPLETED, WhatsAppCampaign::STATUS_PARTIAL])->count(),
            'total_sent' => (int) WhatsAppCampaign::query()->where('business_owner_id', $bo->id)->sum('sent_count'),
        ];

        return view('business.promotions', compact('bo', 'products', 'customers', 'campaigns', 'stats'));
    }

    public function store(Request $request, WhatsAppManager $manager): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'product_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:4000'],
            'media_url' => ['nullable', 'url', 'max:1000'],
            'target_type' => ['required', 'string', 'in:all,vip,non_vip,selected,custom'],
            'target_customer_ids' => ['nullable', 'array'],
            'target_customer_ids.*' => ['integer'],
            'custom_phones' => ['nullable', 'string', 'max:2000'],
            'send_mode' => ['required', 'string', 'in:now,schedule'],
            'scheduled_at' => ['required_if:send_mode,schedule', 'nullable', 'date'],
        ]);

        $product = null;
        if (! empty($validated['product_id'])) {
            $product = Product::query()
                ->where('business_owner_id', $bo->id)
                ->where('id', (int) $validated['product_id'])
                ->first();

            if (! $product) {
                return $this->errorResponse($request, 'Produk yang dipilih tidak sah.');
            }
        }

        $selectedIds = [];
        if ($validated['target_type'] === 'selected') {
            $rawIds = array_filter(array_map('intval', (array) ($validated['target_customer_ids'] ?? [])));
            if (! empty($rawIds)) {
                $selectedIds = Customer::query()
                    ->where('business_owner_id', $bo->id)
                    ->whereIn('id', $rawIds)
                    ->pluck('id')
                    ->all();
            }

            if (empty($selectedIds)) {
                return $this->errorResponse($request, 'Sila pilih sekurang-kurangnya seorang pelanggan.');
            }
        }

        if ($validated['target_type'] === 'custom' && blank($validated['custom_phones'] ?? null)) {
            return $this->errorResponse($request, 'Sila masukkan sekurang-kurangnya satu nombor telefon.');
        }

        $scheduledAt = null;
        if ($validated['send_mode'] === 'schedule') {
            $scheduledAt = Carbon::parse((string) $validated['scheduled_at']);
            if ($scheduledAt->isPast()) {
                return $this->errorResponse($request, 'Tarikh & masa jadual mestilah pada masa hadapan.');
            }
        }

        $campaign = WhatsAppCampaign::create([
            'business_owner_id' => $bo->id,
            'product_id' => $product?->id,
            'title' => trim($validated['title']),
            'message' => trim($validated['message']),
            'media_url' => filled($validated['media_url'] ?? null) ? trim((string) $validated['media_url']) : null,
            'target_type' => $validated['target_type'],
            'target_customer_ids' => $selectedIds,
            'custom_phones' => filled($validated['custom_phones'] ?? null) ? trim((string) $validated['custom_phones']) : null,
            'status' => $validated['send_mode'] === 'schedule' ? WhatsAppCampaign::STATUS_SCHEDULED : WhatsAppCampaign::STATUS_PROCESSING,
            'scheduled_at' => $scheduledAt,
            'created_by' => $user->id,
        ]);

        $targets = $campaign->resolveTargets();
        if (empty($targets)) {
            $campaign->delete();

            return $this->errorResponse($request, 'Tiada penerima telefon WhatsApp ditemui untuk sasaran yang dipilih.');
        }

        $campaign->update(['total_recipients' => count($targets)]);

        if ($validated['send_mode'] === 'schedule') {
            $msg = "Kempen '{$campaign->title}' berjaya dijadualkan untuk {$campaign->total_recipients} penerima pada ".$scheduledAt->format('d M Y, h:i A').'.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'campaign' => $campaign->fresh('product'),
                ]);
            }

            return redirect()->route('business.promotions.index')->with('success', $msg);
        }

        // Send immediately
        $result = $manager->executeCampaign($campaign, false);
        $msg = $result['is_manual']
            ? "Pautan manual WhatsApp sedia untuk {$result['total']} penerima."
            : "Blast selesai: {$result['sent']} berjaya dihantar, {$result['failed']} gagal.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'result' => $result,
                'campaign' => $campaign->fresh('product'),
            ]);
        }

        return redirect()->route('business.promotions.index')->with('success', $msg);
    }

    public function runNow(Request $request, WhatsAppCampaign $campaign, WhatsAppManager $manager): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null && $campaign->business_owner_id === $bo->id, 403);

        $result = $manager->executeCampaign($campaign, false);
        $msg = $result['is_manual']
            ? "Pautan manual WhatsApp sedia untuk {$result['total']} penerima."
            : "Blast selesai: {$result['sent']} berjaya dihantar, {$result['failed']} gagal.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'result' => $result,
            ]);
        }

        return redirect()->route('business.promotions.index')->with('success', $msg);
    }

    public function cancel(Request $request, WhatsAppCampaign $campaign): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null && $campaign->business_owner_id === $bo->id, 403);

        if ($campaign->status === WhatsAppCampaign::STATUS_SCHEDULED) {
            $campaign->update(['status' => WhatsAppCampaign::STATUS_CANCELLED]);
        }

        $msg = "Jadual kempen '{$campaign->title}' telah dibatalkan.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('business.promotions.index')->with('success', $msg);
    }

    public function destroy(Request $request, WhatsAppCampaign $campaign): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        abort_unless($bo !== null && $campaign->business_owner_id === $bo->id, 403);

        $campaign->delete();
        $msg = 'Rekod kempen promosi telah dipadam.';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('business.promotions.index')->with('success', $msg);
    }

    private function errorResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
