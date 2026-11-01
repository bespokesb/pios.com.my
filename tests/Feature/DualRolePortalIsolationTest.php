<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualRolePortalIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dual_role_user_in_business_portal_only_sees_own_data_and_not_downline_bo_data(): void
    {
        // 1. Create Dual-Role User (Partner + Business Owner)
        $dualUser = User::factory()->create([
            'name' => 'Maui Sabily Dual',
            'email' => 'maui_dual@sabily.my',
            'password' => bcrypt('password123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::factory()->create([
            'user_id' => $dualUser->id,
            'partner_no' => 'P000003',
            'applicant_name' => 'Maui Sabily Partner',
        ]);

        $dualBo = BusinessOwner::factory()->create([
            'user_id' => $dualUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000004',
            'applicant_name' => 'Maui Sabily BO',
        ]);

        // 2. Create Downline BO (Norhidayu) under the same Partner
        $downlineUser = User::factory()->create([
            'name' => 'Norhidayu BO',
            'email' => 'norhidayu@example.com',
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $downlineBo = BusinessOwner::factory()->create([
            'user_id' => $downlineUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000005',
            'applicant_name' => 'Norhidayu Muda',
        ]);

        // 3. Create a Customer for Downline BO
        $norhidayuCustomer = Customer::factory()->create([
            'business_owner_id' => $downlineBo->id,
            'partner_id' => $partner->id,
            'name' => 'Pelanggan Norhidayu',
            'customer_no' => 'CUST000100',
            'category' => CustomerCategory::Customer,
        ]);

        // 4. Create a Product for Downline BO
        $norhidayuProduct = Product::factory()->create([
            'business_owner_id' => $downlineBo->id,
            'name' => 'Produk Khas Norhidayu',
            'product_no' => 'PRD000100',
            'status' => ProductStatus::Active,
            'selling_price' => 88.00,
        ]);

        // 5. Create an Order for Downline BO
        $norhidayuOrder = Order::create([
            'business_owner_id' => $downlineBo->id,
            'partner_id' => $partner->id,
            'customer_id' => $norhidayuCustomer->id,
            'order_no' => 'ORD000100',
            'status' => OrderStatus::Paid,
            'subtotal' => 88.00,
            'total' => 88.00,
            'ordered_at' => now(),
        ]);

        // 6. Access Business Owner Portal as Dual User
        // 6a. Check Customers list
        $customerResponse = $this->actingAs($dualUser)->get(route('business.customers.index'));
        $customerResponse->assertOk();
        $customerResponse->assertDontSee('Pelanggan Norhidayu');
        $customerResponse->assertDontSee('CUST000100');

        // 6b. Check Products list
        $productResponse = $this->actingAs($dualUser)->get(route('business.products.index'));
        $productResponse->assertOk();
        $productResponse->assertDontSee('Produk Khas Norhidayu');
        $productResponse->assertDontSee('PRD000100');

        // 6c. Check Transactions list
        $txResponse = $this->actingAs($dualUser)->get(route('business.transactions.index'));
        $txResponse->assertOk();
        $txResponse->assertDontSee('ORD000100');

        // 6d. Check Dashboard stats
        $dashResponse = $this->actingAs($dualUser)->get('/dashboard');
        $dashResponse->assertOk();
        // Dual BO has 0 customers and 0 products of their own
        $dashResponse->assertViewHas('totalCustomers', 0);
        $dashResponse->assertViewHas('productsCount', 0);

        // 7. If Dual User creates their own product, they see it, but Downline BO does not
        $myProduct = Product::create([
            'business_owner_id' => $dualBo->id,
            'name' => 'Produk Saya Sendiri',
            'product_no' => 'PRD000200',
            'status' => ProductStatus::Active,
            'selling_price' => 50.00,
        ]);

        $myProductResponse = $this->actingAs($dualUser)->get(route('business.products.index'));
        $myProductResponse->assertOk();
        $myProductResponse->assertSee('Produk Saya Sendiri');
        $myProductResponse->assertDontSee('Produk Khas Norhidayu');

        // Downline BO checks their products list
        $downlineProductResponse = $this->actingAs($downlineUser)->get(route('business.products.index'));
        $downlineProductResponse->assertOk();
        $downlineProductResponse->assertSee('Produk Khas Norhidayu');
        $downlineProductResponse->assertDontSee('Produk Saya Sendiri');
    }

    public function test_dual_role_user_login_with_bo_number_redirects_to_business_dashboard(): void
    {
        $dualUser = User::factory()->create([
            'email' => 'maui_login@sabily.my',
            'password' => bcrypt('password123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::factory()->create([
            'user_id' => $dualUser->id,
            'partner_no' => 'P000003',
        ]);

        $dualBo = BusinessOwner::factory()->create([
            'user_id' => $dualUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000004',
        ]);

        // Login using BO000004
        $response = $this->post(route('login.submit'), [
            'piosId' => 'BO000004',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticatedAs($dualUser);
    }

    public function test_dual_role_user_login_with_partner_number_redirects_to_partner_dashboard(): void
    {
        $dualUser = User::factory()->create([
            'email' => 'maui_p_login@sabily.my',
            'password' => bcrypt('password123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::factory()->create([
            'user_id' => $dualUser->id,
            'partner_no' => 'P000003',
        ]);

        $dualBo = BusinessOwner::factory()->create([
            'user_id' => $dualUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000004',
        ]);

        // Login using P000003
        $response = $this->post(route('login.submit'), [
            'piosId' => 'P000003',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('filament.partner.pages.dashboard'));
        $this->assertAuthenticatedAs($dualUser);
    }
}
