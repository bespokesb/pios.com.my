<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
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
