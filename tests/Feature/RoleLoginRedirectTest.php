<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_sys_admin_login_redirects_to_owner_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'piosId' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('filament.admin.pages.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_pios_owner_login_redirects_to_owner_dashboard(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::PiosOwner,
            'status' => UserStatus::Active,
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'piosId' => $owner->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('filament.admin.pages.dashboard'));
        $this->assertAuthenticatedAs($owner);
    }

    public function test_partner_login_redirects_to_partner_dashboard(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
            'password' => bcrypt('password123'),
        ]);
        Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'partner_no' => 'P990001',
        ]);

        $response = $this->post(route('login.submit'), [
            'piosId' => 'P990001',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('filament.partner.pages.dashboard'));
        $this->assertAuthenticatedAs($partnerUser);
    }

    public function test_business_owner_login_redirects_to_business_dashboard(): void
    {
        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
            'password' => bcrypt('password123'),
        ]);
        $partner = Partner::factory()->create();
        BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO990001',
        ]);

        $response = $this->post(route('login.submit'), [
            'piosId' => 'BO990001',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticatedAs($boUser);
    }

    public function test_customer_user_login_redirects_to_marketplace(): void
    {
        $customerUser = User::factory()->create([
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'piosId' => $customerUser->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('marketplace.index'));
        $this->assertAuthenticatedAs($customerUser);
    }

    public function test_customer_record_login_without_user_account_redirects_to_marketplace(): void
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $partner->id]);
        $customer = Customer::factory()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $partner->id,
            'customer_no' => 'CST99001',
            'hp_no' => '0198889999',
            'email' => 'customer99@example.com',
        ]);

        $response = $this->post(route('login.submit'), [
            'piosId' => 'CST99001',
            'password' => 'anypassword',
        ]);

        $response->assertRedirect(route('marketplace.index'));
        $this->assertEquals($customer->id, session('market_customer_id'));
    }

    public function test_authenticated_owner_visiting_login_redirects_to_owner_dashboard(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::PiosOwner,
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($owner)->get(route('login'));
        $response->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_authenticated_partner_visiting_login_redirects_to_partner_dashboard(): void
    {
        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        Partner::factory()->create(['user_id' => $partnerUser->id]);

        $response = $this->actingAs($partnerUser)->get(route('login'));
        $response->assertRedirect(route('filament.partner.pages.dashboard'));
    }

    public function test_authenticated_bo_visiting_login_redirects_to_business_dashboard(): void
    {
        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create();
        BusinessOwner::factory()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
        ]);

        $response = $this->actingAs($boUser)->get(route('login'));
        $response->assertRedirect(route('business.dashboard'));
    }

    public function test_welcome_page_shows_appropriate_dashboard_links_per_role(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::PiosOwner,
            'status' => UserStatus::Active,
        ]);
        $response = $this->actingAs($owner)->get('/');
        $response->assertOk();
        $response->assertSee(route('filament.admin.pages.dashboard'));
        $response->assertSee('Go to Owner Dashboard');

        $partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        Partner::factory()->create(['user_id' => $partnerUser->id]);
        $response = $this->actingAs($partnerUser)->get('/');
        $response->assertOk();
        $response->assertSee(route('filament.partner.pages.dashboard'));
        $response->assertSee('Go to Partner Dashboard');

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $partner = Partner::factory()->create();
        BusinessOwner::factory()->create(['user_id' => $boUser->id, 'partner_id' => $partner->id]);
        $response = $this->actingAs($boUser)->get('/');
        $response->assertOk();
        $response->assertSee(route('business.dashboard'));
        $response->assertSee('Go to Business Dashboard');
    }
}
