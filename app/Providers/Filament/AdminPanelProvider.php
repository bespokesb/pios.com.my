<?php

namespace App\Providers\Filament;

use App\Filament\Plugins\PiosLoginPlugin;
use App\Filament\Support\LocaleSwitcher;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
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
                __('pios.nav.group.business_owner'),
                __('pios.nav.group.products'),
                __('pios.nav.group.fees'),
                __('pios.nav.group.reports'),
                __('pios.nav.group.system'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
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
