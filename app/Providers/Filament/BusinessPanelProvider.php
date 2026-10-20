<?php

namespace App\Providers\Filament;

use App\Filament\Business\Pages\BusinessDashboard;
use App\Filament\Pages\Auth\BusinessLogin;
use App\Filament\Pages\BusinessSalesReport;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\IncomingOrders\IncomingOrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\LocaleSwitcher;
use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BusinessPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('business')
            ->path('business')
            ->maxContentWidth('full')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('PIOS BizGrowth Engine')
            ->favicon(asset('favicon-32x32.png'))
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.15rem')
            ->login(BusinessLogin::class)
            ->passwordReset()
            ->profile(isSimple: false)
            ->databaseNotifications()
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('filament.business.mobile-navigation'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.business.styles'),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn () => view('filament.business.scripts'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('filament.partials.sidebar-footer'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn () => view('filament.partials.topbar-end'),
            )
            ->colors([
                'primary' => Color::hex('#134C49'),
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
                'info' => Color::Cyan,
            ])
            ->navigationGroups([
                __('pios.nav.group.customers'),
                __('pios.nav.group.products'),
                __('pios.nav.group.orders'),
                __('pios.nav.group.reports'),
            ])
            ->resources([
                CustomerResource::class,
                ProductResource::class,
                IncomingOrderResource::class,
            ])
            ->pages([
                BusinessDashboard::class,
                BusinessSalesReport::class,
            ])
            ->widgets([])
            ->userMenuItems(LocaleSwitcher::userMenuItems())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
