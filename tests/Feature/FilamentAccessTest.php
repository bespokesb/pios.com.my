<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\FeeSettings\FeeSettingResource;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_route_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_business_login_route_is_available_with_own_branding(): void
    {
        $this->get('/business/login')
            ->assertOk()
            ->assertSee('pios-login-shell-business', escape: false)
            ->assertSee(__('pios.auth.business.heading'))
            ->assertSee('pios-language-switcher', escape: false)
            ->assertSee('brand/pak-itam-logo.png', escape: false);
    }

    public function test_active_sys_admin_can_access_panel_and_system_resources(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($admin);

        $this->assertTrue($admin->fresh()->canAccessPanel(filament()->getPanel('admin')));
        $this->assertTrue($admin->fresh()->canAccessPanel(filament()->getPanel('partner')));
        $this->assertTrue($admin->fresh()->canAccessPanel(filament()->getPanel('business')));
        $this->assertTrue(UserResource::canAccess());
        $this->assertTrue(FeeSettingResource::canAccess());
        $this->assertTrue(AuditLogResource::canAccess());
    }

    public function test_inactive_user_cannot_access_filament_panel(): void
    {
        $inactive = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Inactive,
        ]);

        $this->assertFalse($inactive->fresh()->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_partner_user_cannot_access_system_resources(): void
    {
        $partner = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($partner);

        $this->assertFalse(UserResource::canAccess());
        $this->assertFalse(FeeSettingResource::canAccess());
        $this->assertFalse(AuditLogResource::canAccess());
    }

    public function test_business_owner_can_access_business_panel_but_not_admin_panel(): void
    {
        $businessOwner = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($businessOwner);

        $this->assertTrue($businessOwner->fresh()->canAccessPanel(filament()->getPanel('business')));
        $this->assertFalse($businessOwner->fresh()->canAccessPanel(filament()->getPanel('admin')));
        $this->assertTrue(CustomerResource::canAccess());
        $this->assertTrue(PromotionResource::canAccess());
        $this->assertArrayHasKey('locale_en', filament()->getPanel('business')->getUserMenuItems());
        $this->assertArrayHasKey('locale_ms', filament()->getPanel('business')->getUserMenuItems());

        $this->get('/business')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_partner_can_only_access_partner_panel(): void
    {
        $partner = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($partner);

        $this->assertTrue($partner->fresh()->canAccessPanel(filament()->getPanel('partner')));
        $this->assertFalse($partner->fresh()->canAccessPanel(filament()->getPanel('admin')));
        $this->assertFalse($partner->fresh()->canAccessPanel(filament()->getPanel('business')));

        $this->get('/partner')->assertOk();
        $this->get('/admin')->assertForbidden();
        $this->get('/business')->assertForbidden();
    }

    public function test_only_active_sys_admin_can_impersonate_users(): void
    {
        $sysAdmin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
        $piosOwner = User::factory()->create([
            'role' => UserRole::PiosOwner,
            'status' => UserStatus::Active,
        ]);
        $inactiveSysAdmin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Inactive,
        ]);

        $this->assertTrue($sysAdmin->canImpersonate());
        $this->assertFalse($piosOwner->canImpersonate());
        $this->assertFalse($inactiveSysAdmin->canImpersonate());
    }

    public function test_sys_admin_and_inactive_users_cannot_be_impersonated(): void
    {
        $sysAdmin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
        $businessOwner = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $inactivePartner = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Inactive,
        ]);

        $this->assertFalse($sysAdmin->canBeImpersonated());
        $this->assertTrue($businessOwner->canBeImpersonated());
        $this->assertFalse($inactivePartner->canBeImpersonated());
    }

    public function test_user_table_impersonate_action_is_limited_to_super_admins_and_eligible_targets(): void
    {
        $sysAdmin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
        $piosOwner = User::factory()->create([
            'role' => UserRole::PiosOwner,
            'status' => UserStatus::Active,
        ]);
        $targetSysAdmin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
        $businessOwner = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $inactivePartner = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Inactive,
        ]);

        $this->actingAs($sysAdmin);

        Livewire::test(ListUsers::class)
            ->assertTableActionVisible('impersonate', $businessOwner)
            ->assertTableActionHidden('impersonate', $targetSysAdmin)
            ->assertTableActionHidden('impersonate', $inactivePartner);

        $this->actingAs($piosOwner);

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('impersonate', $businessOwner);
    }

    public function test_sys_admin_can_impersonate_business_owner_from_users_table(): void
    {
        $sysAdmin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
        $businessOwner = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($sysAdmin);

        Livewire::test(ListUsers::class)
            ->callTableAction('impersonate', $businessOwner)
            ->assertRedirect(route('filament.business.pages.dashboard'));

        $this->assertSame($sysAdmin->getKey(), session('impersonated_by'));
        $this->assertAuthenticatedAs($businessOwner);
    }

    public function test_sys_admin_can_impersonate_partner_from_edit_user_page(): void
    {
        $sysAdmin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
        $partner = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($sysAdmin);

        Livewire::test(EditUser::class, ['record' => $partner->getRouteKey()])
            ->callAction('impersonate')
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertSame($sysAdmin->getKey(), session('impersonated_by'));
        $this->assertAuthenticatedAs($partner);
    }

    public function test_business_panel_registers_only_customer_and_product_routes(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('business/customers', $routes);
        $this->assertContains('business/products', $routes);

        // Scoped down to customers + product listing only; everything else is hidden.
        $this->assertNotContains('business/business-owners', $routes);
        $this->assertNotContains('business/business-owner-product-selections', $routes);
        $this->assertNotContains('business/product-proposals', $routes);
        $this->assertNotContains('business/promotions', $routes);
        $this->assertNotContains('business/orders', $routes);
        $this->assertNotContains('business/payments', $routes);
        $this->assertNotContains('business/operational-reports', $routes);
        $this->assertNotContains('business/users', $routes);
        $this->assertNotContains('business/fee-settings', $routes);
        $this->assertNotContains('business/audit-logs', $routes);
    }

    public function test_partner_panel_registers_partner_business_owner_and_customer_routes(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('partner/partners', $routes);
        $this->assertContains('partner/partners/create', $routes);
        $this->assertContains('partner/business-owners', $routes);
        $this->assertContains('partner/business-owners/create', $routes);
        // Partners can view their downstream customers (read + VIP tagging).
        $this->assertContains('partner/customers', $routes);

        // Everything else stays hidden from the partner panel.
        $this->assertNotContains('partner/products', $routes);
        $this->assertNotContains('partner/orders', $routes);
        $this->assertNotContains('partner/fees', $routes);
        $this->assertNotContains('partner/payments', $routes);
        $this->assertNotContains('partner/users', $routes);
        $this->assertNotContains('partner/operational-reports', $routes);
        $this->assertNotContains('partner/audit-logs', $routes);
    }
}
