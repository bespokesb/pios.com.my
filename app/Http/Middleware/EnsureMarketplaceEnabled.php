<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMarketplaceEnabled
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! SystemSetting::isMarketplaceEnabled()) {
            return redirect('/')->with('customer_status', __('pios.marketplace.disabled_notice', [
                'default' => 'Marketplace is currently closed.',
            ]));
        }

        return $next($request);
    }
}
