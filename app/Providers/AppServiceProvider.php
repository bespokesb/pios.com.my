<?php

namespace App\Providers;

use App\Filament\Responses\LoginResponse;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\CustomerNote;
use App\Models\CustomerTag;
use App\Models\Fee;
use App\Models\FeeSetting;
use App\Models\Order;
use App\Models\Partner;
use App\Models\PartnerTier;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductProposal;
use App\Models\ProductType;
use App\Models\Promotion;
use App\Models\Territory;
use App\Models\User;
use App\Models\Vendor;
use App\Policies\AuditLogPolicy;
use App\Policies\BusinessOwnerPolicy;
use App\Policies\BusinessOwnerProductSelectionPolicy;
use App\Policies\CustomerFollowUpPolicy;
use App\Policies\CustomerNotePolicy;
use App\Policies\CustomerPolicy;
use App\Policies\CustomerTagPolicy;
use App\Policies\FeePolicy;
use App\Policies\FeeSettingPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PartnerPolicy;
use App\Policies\PartnerTierPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductProposalPolicy;
use App\Policies\ProductTypePolicy;
use App\Policies\PromotionPolicy;
use App\Policies\TerritoryPolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorPolicy;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Tables\Table;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            LoginResponse::class
        );
    }

    public function boot(): void
    {
        Blade::component('layouts.business', 'business-layout');

        Event::listen(RouteMatched::class, function (): void {
            $routes = Route::getRoutes();
            $ref = new \ReflectionProperty($routes, 'nameList');
            $list = $ref->getValue($routes);
            if (isset($list['business.customers.index']) && ! isset($list['filament.business.resources.customers.index'])) {
                $list['filament.business.resources.customers.index'] = $list['business.customers.index'];
            }
            if (isset($list['business.products.index']) && ! isset($list['filament.business.resources.products.index'])) {
                $list['filament.business.resources.products.index'] = $list['business.products.index'];
            }
            if (isset($list['filament.business.pages.dashboard'])) {
                $list['business.dashboard'] = $list['filament.business.pages.dashboard'];
            }
            $ref->setValue($routes, $list);
        });

        // Format all currency as Malaysian Ringgit (RM ...).
        Number::useLocale('ms_MY');

        // Filament table money() columns default to USD when no currency is
        // passed, and format with config('app.locale'). Force MYR + a Malaysian
        // locale so every money column renders "RM ..." (even bare ->money()).
        Table::configureUsing(function (Table $table): void {
            $table->defaultCurrency('MYR')->defaultNumberLocale('ms_MY');
        });

        // Cross-panel switcher. Auto-lists only the panels the signed-in user can
        // access (hierarchy: admins see all three; a dual-role Partner + Business
        // Owner sees both; single-panel users get nothing to switch to), so no
        // URL juggling is needed. Labels/icons come from our own copy.
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch): void {
            $panelSwitch
                ->simple()
                ->labels([
                    'admin' => __('pios.panel.admin'),
                    'partner' => __('pios.panel.partner'),
                    'business' => __('pios.panel.business'),
                ])
                ->icons([
                    'admin' => 'heroicon-o-cog-6-tooth',
                    'partner' => 'heroicon-o-user-group',
                    'business' => 'heroicon-o-building-storefront',
                ]);
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Partner::class, PartnerPolicy::class);
        Gate::policy(BusinessOwner::class, BusinessOwnerPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerNote::class, CustomerNotePolicy::class);
        Gate::policy(CustomerFollowUp::class, CustomerFollowUpPolicy::class);
        Gate::policy(CustomerTag::class, CustomerTagPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductType::class, ProductTypePolicy::class);
        Gate::policy(Territory::class, TerritoryPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(PartnerTier::class, PartnerTierPolicy::class);
        Gate::policy(FeeSetting::class, FeeSettingPolicy::class);
        Gate::policy(Fee::class, FeePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(ProductProposal::class, ProductProposalPolicy::class);
        Gate::policy(BusinessOwnerProductSelection::class, BusinessOwnerProductSelectionPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
