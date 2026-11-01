<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessOwnerPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isBusinessOwner() && ! $user->isAdmin())) {
            abort(403, 'Unauthorized access to Business Owner portal.');
        }

        if ($user->businessOwner) {
            if ($user->isOverdueLocked()) {
                $partner = $user->businessOwner->partner;
                $partnerName = $partner?->applicant_name;
                $partnerPhone = $partner?->hp_no ?: ($partner?->spouse_hp_no ?: '');
                $contactInfo = '';
                if ($partnerName && $partnerPhone) {
                    $contactInfo = " ({$partnerName} - {$partnerPhone})";
                } elseif ($partnerName) {
                    $contactInfo = " ({$partnerName})";
                } elseif ($partnerPhone) {
                    $contactInfo = " ({$partnerPhone})";
                }

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'piosId' => 'Akaun anda telah disekat kerana bayaran tertunggak melebihi 90 hari. Sila hubungi Partner anda'.$contactInfo.' untuk mengaktifkan semula akaun. (Account locked. Please contact your partner.)',
                ]);
            }

            /** @var Subscription $bgeSub */
            $bgeSub = app(SubscriptionService::class)->ensureInitialBgeSubscription($user->businessOwner);
            $partner = $user->businessOwner->partner;

            $isBgeExpired = ! $bgeSub->isActive();
            $isBgePendingApproval = $bgeSub->status === 'pending_approval';

            view()->share('bgeSubscription', $bgeSub);
            view()->share('sponsoringPartner', $partner);
            view()->share('isBgeExpired', $isBgeExpired);
            view()->share('isBgePendingApproval', $isBgePendingApproval);
        }

        return $next($request);
    }
}
