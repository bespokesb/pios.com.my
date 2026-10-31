<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubscriptionReceiptController extends Controller
{
    public function show(Request $request, SubscriptionPayment $payment): View
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        // Authorization check:
        // 1. SysAdmin / Owner can see any receipt
        // 2. The payer themselves
        // 3. The sponsoring Partner of the BO
        $canView = $user->isSysAdmin()
            || $user->isPiosOwner()
            || $payment->user_id === $user->id
            || ($user->isPartner() && $user->partner && $user->partner->id === $payment->partner_id);

        abort_unless($canView, 403);

        // A receipt is only available once approved/paid
        abort_unless($payment->isApproved() && filled($payment->receipt_no), 404);

        $payment->loadMissing(['product', 'subscription', 'user', 'partner', 'businessOwner', 'approver']);

        return view('documents.subscription-receipt', [
            'payment' => $payment,
            'product' => $payment->product,
            'subscription' => $payment->subscription,
        ]);
    }
}
