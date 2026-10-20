<?php

namespace App\Filament\Responses;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $intended = session()->pull('url.intended');

            // 1. Owner / Admin: ALWAYS redirect to Owner Dashboard (/admin)
            // unless they were specifically navigating to a distinct admin sub-page.
            if ($user->isAdmin()) {
                if ($intended && str_contains($intended, '/admin') && ! str_ends_with($intended, '/admin/login')) {
                    return redirect()->to($intended);
                }

                return redirect()->to(route('filament.admin.pages.dashboard'));
            }

            // 2. Partner: redirect to Partner Dashboard (/partner)
            if ($user->isPartner() && ! $user->isBusinessOwner()) {
                if ($intended && str_contains($intended, '/partner') && ! str_ends_with($intended, '/partner/login')) {
                    return redirect()->to($intended);
                }

                return redirect()->to(route('filament.partner.pages.dashboard'));
            }

            // 3. Business Owner: redirect to Business Dashboard (/business or /dashboard)
            if ($user->isBusinessOwner() && ! $user->isPartner()) {
                if ($intended && (str_contains($intended, '/business') || str_contains($intended, '/dashboard')) && ! str_ends_with($intended, '/business/login')) {
                    return redirect()->to($intended);
                }

                return redirect()->to(route('business.dashboard'));
            }

            // Dual-role user (holds both Partner and Business Owner capability)
            $currentPanelId = Filament::getCurrentPanel()?->getId();
            if ($currentPanelId === 'partner' && $user->canAccessPanel(Filament::getPanel('partner'))) {
                return redirect()->to(route('filament.partner.pages.dashboard'));
            }
            if ($currentPanelId === 'business' && $user->canAccessPanel(Filament::getPanel('business'))) {
                return redirect()->to(route('business.dashboard'));
            }

            // Customer or general fallback
            if ($intended && ! in_array($intended, [
                url('/login'),
                url('/business/login'),
                url('/partner/login'),
                url('/admin/login'),
                route('login'),
                route('filament.business.auth.login'),
            ], true)) {
                return redirect()->to($intended);
            }

            return redirect()->to($user->getDashboardUrl());
        }

        return redirect()->intended(Filament::getUrl());
    }
}
