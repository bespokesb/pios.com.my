<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\IdentifierSequence;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessOwner $bo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Storage::fake('public');

        $this->user = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::factory()->create();

        $this->bo = BusinessOwner::factory()->create([
            'user_id' => $this->user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000888',
            'company_name' => 'Test Company',
        ]);

        IdentifierSequence::updateOrCreate(
            ['key' => 'product_no'],
            ['prefix' => 'PRD', 'next_number' => 1, 'padding' => 6]
        );
    }

    public function test_business_owner_can_update_product_name_spelling_and_details(): void
    {
        $product = Product::factory()->forBusinessOwner($this->bo)->create([
            'name' => 'Wrong Speling Honey 500g',
            'selling_price' => 45.00,
            'cost' => 30.00,
            'stock_quantity' => 10,
            'coverage_area' => 'Nationwide',
            'description' => 'Original description',
            'status' => ProductStatus::Active,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('business.products.update', $product), [
                'name' => 'Correct Spelling Honey 500g',
                'selling_price' => 48.00,
                'cost_price' => 32.00,
                'stock_quantity' => 10,
                'coverage_area' => 'Petaling Jaya, Selangor',
                'description' => 'Updated premium description',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('business.products.index'));
        $response->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('Correct Spelling Honey 500g', $product->name);
        $this->assertEquals(48.00, (float) $product->selling_price);
        $this->assertEquals(32.00, (float) $product->cost);
        $this->assertSame('Petaling Jaya, Selangor', $product->coverage_area);
        $this->assertSame('Updated premium description', $product->description);
    }

    public function test_business_owner_can_add_stock_via_update(): void
    {
        $product = Product::factory()->forBusinessOwner($this->bo)->create([
            'name' => 'Fresh Milk 1L',
            'selling_price' => 12.00,
            'stock_quantity' => 15,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('business.products.update', $product), [
                'name' => 'Fresh Milk 1L',
                'selling_price' => 12.00,
                'stock_quantity' => 15,
                'stock_added' => 25, // adding 25 new units intake
            ]);

        $response->assertRedirect(route('business.products.index'));

        $product->refresh();
        $this->assertSame(40, $product->stock_quantity); // 15 + 25 = 40
    }

    public function test_business_owner_can_delete_product_to_free_quota_slot(): void
    {
        // Create 20 products to hit the limit
        for ($i = 1; $i <= 20; $i++) {
            Product::factory()->forBusinessOwner($this->bo)->create([
                'name' => "Product {$i}",
                'status' => ProductStatus::Active,
            ]);
        }

        $this->assertSame(20, $this->bo->productCount());

        $firstProduct = Product::where('business_owner_id', $this->bo->id)->first();
        $this->assertNotNull($firstProduct);

        // Delete one product
        $response = $this->actingAs($this->user)
            ->delete(route('business.products.destroy', $firstProduct));

        $response->assertRedirect(route('business.products.index'));
        $response->assertSessionHas('success');

        // Product is soft deleted
        $this->assertSoftDeleted('products', ['id' => $firstProduct->id]);

        // Product count is now 19, freeing 1 slot!
        $this->assertSame(19, $this->bo->productCount());
    }

    public function test_business_owner_cannot_edit_or_delete_another_bo_product(): void
    {
        $otherBo = BusinessOwner::factory()->create();
        $otherProduct = Product::factory()->forBusinessOwner($otherBo)->create([
            'name' => 'Other BO Product',
        ]);

        $this->actingAs($this->user)
            ->put(route('business.products.update', $otherProduct), [
                'name' => 'Hacked Name',
            ])
            ->assertForbidden();

        $this->actingAs($this->user)
            ->delete(route('business.products.destroy', $otherProduct))
            ->assertForbidden();
    }

    public function test_propose_supports_separate_optional_video_upload(): void
    {
        $videoFile = UploadedFile::fake()->create('demo_video.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($this->user)
            ->post(route('business.products.propose'), [
                'name' => 'Video Featured Product',
                'customer_price' => 55.00,
                'cost_price' => 35.00,
                'video_file' => $videoFile,
            ]);

        $response->assertRedirect(route('business.products.index'));
        $response->assertSessionHas('success');

        $product = Product::where('name', 'Video Featured Product')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->video_path);
        $this->assertTrue($product->hasVideo());
        $this->assertStringContainsString('products/videos/', $product->video_path);
    }

    public function test_public_product_page_places_video_at_front_and_autoplays(): void
    {
        $product = Product::factory()->forBusinessOwner($this->bo)->create([
            'name' => 'Hero Video Product',
            'product_no' => 'PRD000999',
            'selling_price' => 88.00,
            'status' => ProductStatus::Active,
            'video_path' => 'products/videos/sample.mp4',
            'media_assets' => ['products/photo1.jpg'],
        ]);

        $response = $this->get(route('public.product.show', $product->product_no));
        $response->assertOk();
        $response->assertSee('<video', false);
        $response->assertSee('autoplay', false);
        $response->assertSee('sample.mp4', false);
    }

    public function test_business_owner_can_remove_video_via_update(): void
    {
        $product = Product::factory()->forBusinessOwner($this->bo)->create([
            'name' => 'Product with Video',
            'video_path' => 'products/videos/sample.mp4',
        ]);

        $this->assertTrue($product->hasVideo());

        $response = $this->actingAs($this->user)
            ->put(route('business.products.update', $product), [
                'name' => 'Product with Video',
                'remove_video' => 1,
            ]);

        $response->assertRedirect(route('business.products.index'));

        $product->refresh();
        $this->assertNull($product->video_path);
        $this->assertFalse($product->hasVideo());
    }
}
