<?php

namespace App\Http\Controllers\Business;

use App\Enums\FeeStatus;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\FeeService;
use App\Services\SubscriptionService;
use App\Services\WhatsAppPassSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        $query = Fee::query()->visibleTo($user)->with(['payments', 'partner']);
        if (! $user->isAdmin() && $bo) {
            $query->where('business_owner_id', $bo->id);
        }
        $fees = $query->latest('due_date')->get();

        $feePaid = 0.0;
        $feeOutstanding = 0.0;
        $feeOverdueCount = 0;
        $feePendingCount = 0;

        foreach ($fees as $fee) {
            $amount = (float) $fee->amount;
            if ($fee->status === FeeStatus::Paid) {
                $feePaid += $amount;
            } elseif ($fee->status !== FeeStatus::Waived) {
                $feeOutstanding += $amount;
                $isOverdue = $fee->due_date && $fee->due_date->isPast() && $fee->due_date->diffInDays(now()) >= 91;
                if ($isOverdue) {
                    $feeOverdueCount++;
                } else {
                    $feePendingCount++;
                }
            }
        }

        $bgeProduct = SubscriptionProduct::where('code', 'bge')->first();
        $bgePrice = (float) ($bgeProduct?->price ?? 30.00);
        $bgeSub = $bo ? app(SubscriptionService::class)->ensureInitialBgeSubscription($bo) : null;
        $bgePayments = collect();

        $subPaid = 0.0;
        $bgeOutstanding = 0.0;
        $bgeOverdueCount = 0;
        $bgePendingCount = 0;

        if ($bo) {
            $bgePayments = SubscriptionPayment::query()
                ->where('business_owner_id', $bo->id)
                ->when($bgeProduct, fn ($q) => $q->where('subscription_product_id', $bgeProduct->id))
                ->latest()
                ->get();

            $subPaid = (float) $bgePayments->where('status', 'approved')->sum('amount');

            if ($bgeSub) {
                $hasPendingPayment = $bgePayments->where('status', 'pending_approval')->isNotEmpty()
                    || $bgeSub->status === 'pending_approval';

                if ($hasPendingPayment) {
                    $bgeOutstanding += $bgePrice;
                    $bgePendingCount++;
                } elseif (! $bgeSub->isActive()) {
                    $bgeOutstanding += $bgePrice;
                    $daysExpired = $bgeSub->expires_at && $bgeSub->expires_at->isPast()
                        ? (int) $bgeSub->expires_at->diffInDays(now())
                        : 0;

                    if ($daysExpired >= 91) {
                        $bgeOverdueCount++;
                    } else {
                        $bgePendingCount++;
                    }
                }
            }
        }

        $totalPaid = $feePaid + $subPaid;
        $totalOutstanding = $feeOutstanding + $bgeOutstanding;
        $overdueCount = $feeOverdueCount + $bgeOverdueCount;
        $pendingCount = $feePendingCount + $bgePendingCount;

        return view('business.fees', compact(
            'fees',
            'bgeSub',
            'bgeProduct',
            'bgePayments',
            'totalPaid',
            'totalOutstanding',
            'overdueCount',
            'pendingCount'
        ));
    }

    public function pay(Request $request, Fee $fee): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin() && $fee->business_owner_id !== $user->businessOwner?->id) {
            abort(403);
        }

        if ($fee->status === FeeStatus::Paid) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Yuran ini sudah pun selesai dibayar.'], 422);
            }

            return redirect()->route('business.fees.index')
                ->with('error', 'Yuran ini sudah pun selesai dibayar.');
        }

        if ($fee->status === FeeStatus::Pending) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Bayaran sedang menunggu pengesahan daripada Partner anda.'], 422);
            }

            return redirect()->route('business.fees.index')
                ->with('error', 'Bayaran untuk yuran ini telah dihantar dan sedang menunggu pengesahan Partner anda.');
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = app(FeeService::class)->submitPayment(
            fee: $fee,
            actor: $user,
            paymentMethod: $validated['payment_method'],
            referenceNo: $validated['reference_no'] ?? null,
            notes: $validated['notes'] ?? null
        );

        $partner = $fee->partner ?: $user->businessOwner?->partner;
        $waUrl = WhatsAppPassSender::forFeePaymentToPartner(
            $partner,
            $user->businessOwner,
            $fee,
            $payment
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bayaran yuran berjaya dihantar. Menunggu pengesahan daripada Partner anda.',
                'whatsapp_url' => $waUrl,
            ]);
        }

        return redirect()->route('business.fees.index')
            ->with('success', 'Bayaran yuran berjaya dihantar. Menunggu pengesahan daripada Partner anda.')
            ->with('whatsapp_url', $waUrl);
    }
}
