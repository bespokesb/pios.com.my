<?php

namespace App\Http\Controllers\Business;

use App\Enums\FeeStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\User;
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

        $query = Fee::query()->visibleTo($user)->with(['payments', 'partner']);
        $fees = $query->latest('due_date')->get();

        $baseQuery = Fee::query()->visibleTo($user);
        $totalPaid = (clone $baseQuery)->where('status', FeeStatus::Paid)->sum('amount');
        $totalOutstanding = (clone $baseQuery)->whereIn('status', [FeeStatus::Pending, FeeStatus::Overdue])->sum('amount');
        $overdueCount = (clone $baseQuery)->where('status', FeeStatus::Overdue)->count();
        $pendingCount = (clone $baseQuery)->where('status', FeeStatus::Pending)->count();

        return view('business.fees', compact(
            'fees',
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

        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Payment::create([
            'partner_id' => $fee->partner_id,
            'business_owner_id' => $fee->business_owner_id,
            'customer_id' => $fee->customer_id,
            'fee_id' => $fee->id,
            'amount' => $fee->amount,
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'notes' => ($validated['reference_no'] ? "Ref: {$validated['reference_no']}. " : '').($validated['notes'] ?? ''),
        ]);

        $fee->update([
            'status' => FeeStatus::Paid,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
            ]);
        }

        return redirect()->route('business.fees.index')
            ->with('success', 'Payment for fee has been processed.');
    }
}
