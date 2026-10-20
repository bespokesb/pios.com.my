<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\BusinessOwner;
use App\Models\Product;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAdminControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_marketplace_is_accessible_when_enabled(): void
    {
        SystemSetting::setMarketplaceEnabled(true);

        $response = $this->get('/market');

        $response->assertStatus(200);
    }

    public function test_marketplace_redirects_to_home_when_disabled_by_admin(): void
    {
        SystemSetting::setMarketplaceEnabled(false);

        $response = $this->get('/market');

        $response->assertRedirect('/');
    }

    public function test_system_setting_toggles_marketplace_status(): void
    {
        SystemSetting::setMarketplaceEnabled(false);
        $this->assertFalse(SystemSetting::isMarketplaceEnabled());

        SystemSetting::setMarketplaceEnabled(true);
        $this->assertTrue(SystemSetting::isMarketplaceEnabled());
    }

    public function test_product_page_hides_marketplace_button_when_disabled(): void
    {
        $bo = BusinessOwner::factory()->create();
        $product = Product::factory()->create([
            'business_owner_id' => $bo->id,
            'status' => ProductStatus::Active,
        ]);

        SystemSetting::setMarketplaceEnabled(false);

        $response = $this->get('/p/'.$product->product_no);

        $response->assertStatus(200);
        $response->assertDontSee('&larr; Marketplace', false);
    }

    public function test_public_product_login_is_hidden_by_default(): void
    {
        $bo = BusinessOwner::factory()->create();
        $product = Product::factory()->create([
            'business_owner_id' => $bo->id,
            'status' => ProductStatus::Active,
        ]);

        $this->assertFalse(SystemSetting::isPublicProductLoginEnabled());

        $response = $this->get('/p/'.$product->product_no);

        $response->assertStatus(200);
        $response->assertDontSee('isLoginOpen = true', false);
        $response->assertDontSee('Registered Customer?', false);
    }

    public function test_public_product_login_can_be_enabled_via_system_setting(): void
    {
        $bo = BusinessOwner::factory()->create();
        $product = Product::factory()->create([
            'business_owner_id' => $bo->id,
            'status' => ProductStatus::Active,
        ]);

        SystemSetting::setPublicProductLoginEnabled(true);
        $this->assertTrue(SystemSetting::isPublicProductLoginEnabled());

        $response = $this->get('/p/'.$product->product_no);

        $response->assertStatus(200);
        $response->assertSee('isLoginOpen = true', false);
        $response->assertSee('Registered Customer?', false);
    }

    public function test_public_product_order_saves_all_address_fields(): void
    {
        $bo = BusinessOwner::factory()->create();
        $product = Product::factory()->create([
            'business_owner_id' => $bo->id,
            'status' => ProductStatus::Active,
            'selling_price' => 50.00,
        ]);

        $response = $this->post('/p/'.$product->product_no.'/order', [
            'name' => 'Ahmad Test',
            'phone' => '0123456789',
            'quantity' => 2,
            'address' => 'No 123, Jalan Mawar 1',
            'address_line_2' => 'Taman Mawar Indah',
            'address_line_3' => 'Seksyen 4',
            'poskod' => '40000',
            'bandar' => 'Shah Alam',
            'negeri' => 'Selangor',
            'note' => 'Please leave at guard house',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/p/'.$product->product_no);

        $this->assertDatabaseHas('customers', [
            'name' => 'Ahmad Test',
            'address' => 'No 123, Jalan Mawar 1',
            'address_line_2' => 'Taman Mawar Indah',
            'address_line_3' => 'Seksyen 4',
            'poskod' => '40000',
            'bandar' => 'Shah Alam',
            'negeri' => 'Selangor',
        ]);
    }
}
