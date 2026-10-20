<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createSampleProduct(string $name = 'Sample Item', float $price = 50.00, ?ProductType $type = null): Product
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->forPartner($partner)->create();
        $type = $type ?? ProductType::firstOrCreate(
            ['code' => 'health'],
            ['name' => 'Health', 'status' => 'active']
        );

        return Product::factory()->forBusinessOwner($bo)->create([
            'product_type_id' => $type->id,
            'name' => $name,
            'selling_price' => $price,
            'status' => ProductStatus::Active,
        ]);
    }

    public function test_marketplace_page_renders_active_products(): void
    {
        $product = $this->createSampleProduct('Organic Honey', 45.00);

        $response = $this->get('/market');

        $response->assertStatus(200);
        $response->assertSee('Organic Honey');
        $response->assertSee('45.00');
    }

    public function test_marketplace_search_filters_products(): void
    {
        $this->createSampleProduct('Super Herb Tea', 30.00);
        $this->createSampleProduct('Vitamin C Pack', 20.00);

        $response = $this->get('/market?search=Herb');

        $response->assertStatus(200);
        $response->assertSee('Super Herb Tea');
        $response->assertDontSee('Vitamin C Pack');
    }

    public function test_marketplace_category_filter(): void
    {
        $healthType = ProductType::firstOrCreate(
            ['code' => 'health_care'],
            ['name' => 'Health Care', 'status' => 'active']
        );
        $bookType = ProductType::firstOrCreate(
            ['code' => 'reading_book'],
            ['name' => 'Books', 'status' => 'active']
        );

        $healthProduct = $this->createSampleProduct('Health Book', 25.00, $healthType);
        $bookProduct = $this->createSampleProduct('Novel Book', 15.00, $bookType);

        $response = $this->get('/market?category=health_care');

        $response->assertStatus(200);
        $response->assertSee('Health Book');
        $response->assertDontSee('Novel Book');
    }

    public function test_adding_and_updating_cart(): void
    {
        $product = $this->createSampleProduct('Multivitamin', 60.00);

        $response = $this->post('/market/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, session('market_cart')[$product->id]['quantity']);

        // Update quantity
        $this->post('/market/cart/update', [
            'product_id' => $product->id,
            'action' => 'update',
            'quantity' => 5,
        ]);

        $this->assertEquals(5, session('market_cart')[$product->id]['quantity']);

        // Remove item
        $this->post('/market/cart/update', [
            'product_id' => $product->id,
            'action' => 'remove',
        ]);

        $this->assertArrayNotHasKey($product->id, session('market_cart', []));
    }

    public function test_customer_login_identification(): void
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->forPartner($partner)->create();
        $customer = Customer::factory()->forBusinessOwner($bo)->create([
            'name' => 'Siti Nurhaliza',
            'hp_no' => '0198887766',
            'kp_no' => '900101015566',
            'email' => 'siti@example.com',
        ]);

        // Login by phone
        $response = $this->post('/market/login', [
            'identifier' => '0198887766',
        ]);
        $response->assertRedirect();
        $this->assertEquals($customer->id, session('market_customer_id'));
        $this->assertEquals('Siti Nurhaliza', session('market_customer_name'));

        // Logout
        $this->post('/market/logout');
        $this->assertNull(session('market_customer_id'));

        // Login by IC (kp_no)
        $response = $this->post('/market/login', [
            'identifier' => '900101015566',
        ]);
        $response->assertRedirect();
        $this->assertEquals($customer->id, session('market_customer_id'));

        // Logout
        $this->post('/market/logout');

        // Login by email
        $response = $this->post('/market/login', [
            'identifier' => 'siti@example.com',
        ]);
        $response->assertRedirect();
        $this->assertEquals($customer->id, session('market_customer_id'));
    }

    public function test_customer_registration_page_and_process(): void
    {
        $response = $this->get('/market/register');
        $response->assertStatus(200);
        $response->assertSee('Register Customer');

        $response = $this->post('/market/register', [
            'name' => 'Ahmad Razak',
            'phone' => '0123456789',
            'email' => 'ahmad@example.com',
            'kp_no' => '880505014433',
            'address' => '45 Jalan Sultan, Shah Alam',
        ]);

        $response->assertRedirect('/market');
        $this->assertDatabaseHas('customers', [
            'name' => 'Ahmad Razak',
            'kp_no' => '880505014433',
            'email' => 'ahmad@example.com',
        ]);
        $this->assertEquals('Ahmad Razak', session('market_customer_name'));
    }

    public function test_checkout_places_order_and_clears_cart(): void
    {
        $product = $this->createSampleProduct('Omega 3 Oil', 80.00);

        // Add to cart
        $this->post('/market/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->post('/market/checkout', [
            'name' => 'Zamri Hassan',
            'phone' => '0129998877',
            'address' => '123 Jalan Ampang, Kuala Lumpur',
            'note' => 'Leave at door',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Thank You! Your Order is Placed.');

        // Cart is cleared after checkout
        $this->assertEmpty(session('market_cart', []));

        // Order created in DB
        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals(160.00, (float) $order->total);
    }
}
