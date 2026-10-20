<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\BusinessOwner;
use App\Models\MalaysiaLocation;
use App\Models\Product;
use App\Models\User;
use App\Support\MalaysianLocations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MalaysiaLocationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_malaysian_locations_default_dataset_and_database_sync(): void
    {
        // 1. Verify default canonical dataset
        $dataset = MalaysianLocations::defaultDataset();
        $this->assertCount(16, $dataset);
        $this->assertArrayHasKey('Selangor', $dataset);
        $this->assertArrayHasKey('Johor', $dataset);
        $this->assertArrayHasKey('Wilayah Persekutuan Kuala Lumpur', $dataset);
        $this->assertContains('Shah Alam', $dataset['Selangor']['cities']);
        $this->assertContains('40000', $dataset['Selangor']['postcodes']);

        // 2. Seed database records
        MalaysiaLocation::create([
            'state' => 'Selangor',
            'city' => 'Shah Alam',
            'postcode' => '40000',
        ]);
        MalaysiaLocation::create([
            'state' => 'Johor',
            'city' => 'Johor Bahru',
            'postcode' => '80000',
        ]);

        $this->assertTrue(MalaysianLocations::hasDatabaseSet());
        $fromDb = MalaysianLocations::fromDatabase();
        $this->assertArrayHasKey('Selangor', $fromDb);
        $this->assertArrayHasKey('Johor', $fromDb);
        $this->assertContains('Shah Alam', $fromDb['Selangor']['cities']);
        $this->assertContains('40000', $fromDb['Selangor']['postcodes']);

        // 3. Lookup helper
        $lookup = MalaysianLocations::lookupByPostcode('40000');
        $this->assertNotNull($lookup);
        $this->assertSame('Selangor', $lookup['state']);
    }

    public function test_public_product_form_renders_separated_name_and_phone_lines(): void
    {
        $bo = BusinessOwner::factory()->create();
        $product = Product::factory()->create([
            'business_owner_id' => $bo->id,
            'status' => ProductStatus::Active,
            'selling_price' => 50.00,
        ]);

        $response = $this->get(route('public.product.show', ['product' => $product->product_no]));
        $response->assertOk();

        // Verify Name and Phone are no longer wrapped in a 2-column grid
        $response->assertDontSee('<div class="grid gap-4 sm:grid-cols-2">', false);

        // Verify "Your Name" input exists
        $response->assertSee('placeholder="Your full name"', false);
        $response->assertSee('id="name"', false);

        // Verify "Phone * Format WhatsApp (+60)" exists on its own
        $response->assertSee('Format WhatsApp (+60)');
        $response->assertSee('id="publicPhoneContainer"', false);
        $response->assertSee('id="phone"', false);

        // Verify shared location script is rendered
        $response->assertSee('window.attachMalaysiaLocationSync', false);
        $response->assertSee('id="postcodeSelect"', false);
        $response->assertSee('id="citySelect"', false);
        $response->assertSee('id="stateSelect"', false);
    }

    public function test_bo_customer_page_renders_shared_location_sync(): void
    {
        $user = User::factory()->create(['role' => 'business_owner']);
        $bo = BusinessOwner::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('business.customers.index'));
        $response->assertOk();

        // Verify customer modal contains shared location sync
        $response->assertSee('window.attachMalaysiaLocationSync', false);
        $response->assertSee('id="postcodeSelect"', false);
        $response->assertSee('id="citySelect"', false);
        $response->assertSee('id="stateSelect"', false);
    }
}
