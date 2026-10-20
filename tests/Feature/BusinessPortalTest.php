<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Fee;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessOwner $bo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
            'password' => bcrypt('secret123'),
        ]);

        $partner = Partner::factory()->create();

        $this->bo = BusinessOwner::factory()->create([
            'user_id' => $this->user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000001',
            'applicant_name' => 'Lenny Muller',
            'ic_no' => '900101015555',
            'hp_no' => '0123456789',
        ]);
    }

    public function test_guest_can_view_landing_page(): void
    {
        Product::factory()->create([
            'name' => 'Real Database Organic Matcha',
            'status' => ProductStatus::Active,
            'selling_price' => 79.90,
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('PIOS BizGrowth Engine');
        $response->assertSee('HOW PIOS WORKS');
        $response->assertSee('Real Database Organic Matcha');
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('PIOS ID');
        $response->assertSee('Password');
    }

    public function test_user_can_login_with_pios_id_or_ic(): void
    {
        $response = $this->post(route('login.submit'), [
            'piosId' => '900101015555',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_user_can_login_with_business_owner_no(): void
    {
        $response = $this->post(route('login.submit'), [
            'piosId' => 'BO000001',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_user_can_login_with_phone_number(): void
    {
        $response = $this->post(route('login.submit'), [
            'piosId' => '012-345 6789',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_user_can_login_with_email(): void
    {
        $response = $this->post(route('login.submit'), [
            'piosId' => $this->user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_authenticated_bo_can_view_dashboard_with_kpis(): void
    {
        Customer::factory()->create([
            'business_owner_id' => $this->bo->id,
            'category' => CustomerCategory::Customer,
        ]);

        $response = $this->actingAs($this->user)->get(route('business.dashboard'));
        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Total Customers');
        $response->assertSee('Customer Pipeline');
    }

    public function test_authenticated_bo_can_view_customer_management_page(): void
    {
        $customer = Customer::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Carmen Reyes',
            'category' => CustomerCategory::Customer,
            'is_vip' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('business.customers.index'));
        $response->assertOk();
        $response->assertSee('Carmen Reyes');
        $response->assertSee('VIP');
    }

    public function test_authenticated_bo_can_register_new_customer(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.customers.store'), [
            'name' => 'Ahmad Albab',
            'hp_no' => '0198887777',
            'category' => 'customer',
            'is_vip' => false,
        ]);

        $response->assertRedirect(route('business.customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Ahmad Albab',
            'business_owner_id' => $this->bo->id,
        ]);
    }

    public function test_authenticated_bo_can_register_customer_with_all_modal_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.customers.store'), [
            'name' => 'Siti Nurhaliza',
            'contact_person' => 'Kak Siti',
            'email' => 'siti@example.com',
            'hp_no' => '+6011-1000 0000',
            'address' => 'No 123 Jalan Ampang',
            'address_line_2' => 'Taman Mewah',
            'address_line_3' => 'Blok B-02-05',
            'poskod' => '50000',
            'bandar' => 'Kuala Lumpur',
            'negeri' => 'Wilayah Persekutuan Kuala Lumpur',
        ]);

        $response->assertRedirect(route('business.customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Siti Nurhaliza',
            'nick_name' => 'Kak Siti',
            'email' => 'siti@example.com',
            'address' => 'No 123 Jalan Ampang',
            'address_line_2' => 'Taman Mewah',
            'address_line_3' => 'Blok B-02-05',
            'poskod' => '50000',
            'bandar' => 'Kuala Lumpur',
            'negeri' => 'Wilayah Persekutuan Kuala Lumpur',
            'business_owner_id' => $this->bo->id,
        ]);
    }

    public function test_register_customer_rejects_duplicate_phone(): void
    {
        Customer::factory()->create([
            'business_owner_id' => $this->bo->id,
            'partner_id' => $this->bo->partner_id,
            'name' => 'Existing Customer',
            'hp_no' => '0123334444',
        ]);

        // Attempt duplicate with formatted phone number
        $response = $this->actingAs($this->user)->post(route('business.customers.store'), [
            'name' => 'Another Customer',
            'hp_no' => '+6012-333 4444',
        ]);

        $response->assertSessionHasErrors(['hp_no']);
    }

    public function test_check_phone_endpoint(): void
    {
        Customer::factory()->create([
            'business_owner_id' => $this->bo->id,
            'partner_id' => $this->bo->partner_id,
            'name' => 'Existing Phone Owner',
            'hp_no' => '0189998888',
        ]);

        // Check duplicate
        $dupResponse = $this->actingAs($this->user)->getJson(route('business.customers.check-phone', [
            'hp_no' => '+6018-999 8888',
        ]));
        $dupResponse->assertOk();
        $dupResponse->assertJson([
            'valid' => true,
            'available' => false,
            'duplicate' => true,
        ]);

        // Check unique/available
        $availResponse = $this->actingAs($this->user)->getJson(route('business.customers.check-phone', [
            'hp_no' => '0171112233',
        ]));
        $availResponse->assertOk();
        $availResponse->assertJson([
            'valid' => true,
            'available' => true,
            'duplicate' => false,
        ]);

        // Check insufficient digits / garbage phone returns valid: false
        $shortResponse = $this->actingAs($this->user)->getJson(route('business.customers.check-phone', [
            'hp_no' => '111111',
        ]));
        $shortResponse->assertOk();
        $shortResponse->assertJson([
            'valid' => false,
            'available' => false,
            'duplicate' => false,
        ]);
    }

    public function test_register_customer_rejects_insufficient_digits_phone(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.customers.store'), [
            'name' => 'Incomplete Phone Customer',
            'hp_no' => '111111',
        ]);

        $response->assertSessionHasErrors(['hp_no']);
    }

    public function test_register_customer_accepts_valid_phone_with_plus_60(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.customers.store'), [
            'name' => 'Complete Phone Customer',
            'hp_no' => '+6012-345 6789',
        ]);

        $response->assertRedirect(route('business.customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Complete Phone Customer',
            'hp_no' => '60123456789',
            'hp_no_normalized' => '60123456789',
        ]);
    }

    public function test_register_customer_auto_assigns_contact_person_to_business_owner(): void
    {
        $response = $this->actingAs($this->user)->post(route('business.customers.store'), [
            'name' => 'Auto Contact Customer',
            'hp_no' => '+6012-888 7777',
        ]);

        $response->assertRedirect(route('business.customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'Auto Contact Customer',
            'nick_name' => $this->bo->applicant_name,
        ]);
    }

    public function test_authenticated_bo_can_view_products_and_propose_product(): void
    {
        $product = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'COSRX Snail Mucin',
            'selling_price' => 108.38,
            'status' => ProductStatus::Active,
        ]);

        $response = $this->actingAs($this->user)->get(route('business.products.index'));
        $response->assertOk();
        $response->assertSee('COSRX Snail Mucin');
        $response->assertSee('108.38');

        // Propose product
        $propResponse = $this->actingAs($this->user)->post(route('business.products.propose'), [
            'name' => 'Organic Honey Extra',
            'partner_price' => 50,
            'customer_price' => 80,
            'description' => 'Pure organic wild honey.',
        ]);

        $propResponse->assertRedirect(route('business.products.index'));
        $this->assertDatabaseHas('product_proposals', [
            'name' => 'Organic Honey Extra',
            'business_owner_id' => $this->bo->id,
        ]);
    }

    public function test_authenticated_bo_can_submit_product_with_cost_price_stock_and_media(): void
    {
        Storage::fake('public');

        $image1 = UploadedFile::fake()->image('honey_front.jpg', 600, 600)->size(1500);
        $image2 = UploadedFile::fake()->image('honey_nutrition.png', 600, 600)->size(2000);
        $video = UploadedFile::fake()->create('intro.mp4', 8000, 'video/mp4');

        $response = $this->actingAs($this->user)->post(route('business.products.propose'), [
            'name' => 'Raw Honeycomb Premium',
            'cost_price' => 45.50,
            'customer_price' => 75.00,
            'area' => 'Kuala Lumpur',
            'description' => '100% pure raw honeycomb harvested from natural bee farms.',
            'stock_in_hand' => 50,
            'stock_added' => 25,
            'stock_date_added' => '2026-10-08',
            'media_files' => [$image1, $image2, $video],
        ]);

        $response->assertRedirect(route('business.products.index'));

        $proposal = ProductProposal::query()->where('name', 'Raw Honeycomb Premium')->first();
        $this->assertNotNull($proposal);
        $this->assertEquals(45.50, (float) $proposal->cost_price);
        $this->assertEquals(50, $proposal->stock_in_hand);
        $this->assertEquals(25, $proposal->stock_added);
        $this->assertEquals('2026-10-08', $proposal->stock_date_added->format('Y-m-d'));
        $this->assertCount(3, $proposal->media_assets);

        // Product in catalog has combined total stock (50 + 25 = 75)
        $product = Product::query()->where('name', 'Raw Honeycomb Premium')->first();
        $this->assertNotNull($product);
        $this->assertEquals(75, $product->stock_quantity);
        $this->assertEquals(45.50, (float) $product->cost);
        $this->assertEquals(75.00, (float) $product->selling_price);
        $this->assertCount(3, $product->media_assets);

        // Files stored in storage
        foreach ($product->media_assets as $path) {
            Storage::disk('public')->assertExists($path);
        }

        // Test max 4 files validation error
        $tooManyFiles = [
            UploadedFile::fake()->image('1.jpg'),
            UploadedFile::fake()->image('2.jpg'),
            UploadedFile::fake()->image('3.jpg'),
            UploadedFile::fake()->image('4.jpg'),
            UploadedFile::fake()->image('5.jpg'),
        ];
        $errorResponse = $this->actingAs($this->user)->post(route('business.products.propose'), [
            'name' => 'Excess Media Product',
            'cost_price' => 10,
            'customer_price' => 20,
            'media_files' => $tooManyFiles,
        ]);
        $errorResponse->assertSessionHasErrors('media_files');
    }

    public function test_authenticated_bo_can_view_transactions_and_fees(): void
    {
        $customer = Customer::factory()->create([
            'business_owner_id' => $this->bo->id,
            'partner_id' => $this->bo->partner_id,
        ]);

        $order = Order::create([
            'business_owner_id' => $this->bo->id,
            'partner_id' => $this->bo->partner_id,
            'customer_id' => $customer->id,
            'order_no' => 'ORD000001',
            'subtotal' => 250.00,
            'total' => 250.00,
            'status' => OrderStatus::Paid,
            'ordered_at' => now(),
            'paid_at' => now(),
        ]);

        $fee = Fee::create([
            'business_owner_id' => $this->bo->id,
            'partner_id' => $this->bo->partner_id,
            'amount' => 50.00,
            'fee_type' => FeeType::BusinessOwnerMonthly,

            'status' => FeeStatus::Pending,
            'due_date' => now()->addDays(7),
        ]);

        $transResponse = $this->actingAs($this->user)->get(route('business.transactions.index'));
        $transResponse->assertOk();
        $transResponse->assertSee($order->order_no);

        $feeResponse = $this->actingAs($this->user)->get(route('business.fees.index'));
        $feeResponse->assertOk();
        $feeResponse->assertSee('50.00');

        $reportResponse = $this->actingAs($this->user)->get(route('business.reports.index'));
        $reportResponse->assertOk();
        $reportResponse->assertSee('Reports', false);
    }

    public function test_authenticated_bo_sees_mobile_navigation_drawer_with_all_links(): void
    {
        $response = $this->actingAs($this->user)->get(route('business.dashboard'));
        $response->assertOk();
        $response->assertSee('id="mobileMenuToggleBtn"', false);
        $response->assertSee('id="fullscreenMenu"', false);
        $response->assertSee(route('business.dashboard'), false);
        $response->assertSee(route('business.customers.index'), false);
        $response->assertSee(route('business.products.index'), false);
        $response->assertSee(route('business.transactions.index'), false);
        $response->assertSee(route('business.fees.index'), false);
        $response->assertSee(route('business.reports.index'), false);
        $response->assertSee(route('logout'), false);
        $response->assertSee('id="fsMenuCloseBtn"', false);
    }

    public function test_all_business_slash_routes_render_responsive_layout_with_mobile_drawer(): void
    {
        $businessUris = [
            '/business',
            '/business/customers',
            '/business/products',
            '/business/incoming-orders',
            '/business/business-sales-report',
        ];

        foreach ($businessUris as $uri) {
            $response = $this->actingAs($this->user)->get($uri);
            $response->assertOk();
            $response->assertSee('id="mobileMenuToggleBtn"', false);
            $response->assertSee('id="fullscreenMenu"', false);
        }

        $portalUris = [
            '/dashboard',
            '/customers',
            '/products',
            '/transactions',
            '/fees',
            '/reports',
        ];

        foreach ($portalUris as $uri) {
            $response = $this->actingAs($this->user)->get($uri);
            $response->assertOk();
            $response->assertSee('id="mobileMenuToggleBtn"', false);
            $response->assertSee('id="fullscreenMenu"', false);
        }
    }
}
