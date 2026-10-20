<?php

namespace App\Providers\Filament;

use App\Filament\Pages\PartnerNetworkTree;
use App\Filament\Plugins\PiosLoginPlugin;
use App\Filament\Resources\BusinessOwners\BusinessOwnerResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Partners\PartnerResource;
use App\Filament\Support\LocaleSwitcher;
use App\Filament\Widgets\PartnerBusinessOwners;
use App\Filament\Widgets\PartnerQuotaWidget;
use App\Filament\Widgets\PiosStatsOverview;
use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PartnerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('partner')
            ->path('partner')
            ->maxContentWidth('full')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('PIOS BizGrowth Engine')
            ->favicon(asset('favicon-32x32.png'))
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.15rem')
            ->plugin(PiosLoginPlugin::make())
            ->passwordReset()
            ->profile(isSimple: false)
            ->colors([
                'primary' => Color::hex('#174A78'),
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Olive,
            ])
            ->navigationGroups([
                __('pios.nav.group.register'),
                'Customers',
            ])
            ->resources([
                PartnerResource::class,
                BusinessOwnerResource::class,
                CustomerResource::class,
            ])
            ->pages([
                Dashboard::class,
                PartnerNetworkTree::class,
            ])
            ->widgets([
                PartnerQuotaWidget::class,
                PiosStatsOverview::class,
                PartnerBusinessOwners::class,
                AccountWidget::class,
            ])
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
