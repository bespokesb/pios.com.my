<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\BusinessOwner;
use App\Models\IdentifierSequence;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BusinessOwnerProductLimitTest extends TestCase
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
        ]);

        $partner = Partner::factory()->create();

        $this->bo = BusinessOwner::factory()->create([
            'user_id' => $this->user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000999',
            'applicant_name' => 'Limit Test BO',
        ]);
    }

    public function test_business_owner_products_page_shows_product_usage_and_limit(): void
    {
        $response = $this->actingAs($this->user)->get(route('business.products.index'));

        $response->assertOk();
        $response->assertSee('Product Limit: 0 / 20 Products');
        $response->assertSee('20 Slots Available');
        $response->assertSee('0 / 20');
        $response->assertSee('Products Used');
    }

    public function test_business_owner_can_create_products_up_to_limit(): void
    {
        IdentifierSequence::updateOrCreate(
            ['key' => 'product_no'],
            ['prefix' => 'PRD', 'next_number' => 20, 'padding' => 6]
        );

        // Create 19 products for the Business Owner
        for ($i = 1; $i <= 19; $i++) {
            Product::query()->create([
                'business_owner_id' => $this->bo->id,
                'product_no' => sprintf('PRD%06d', $i),
                'name' => "Product {$i}",
                'selling_price' => 10.00,
                'status' => ProductStatus::Active,
                'created_by' => $this->user->id,
            ]);
        }

        $this->assertSame(19, $this->bo->fresh()->productCount());
        $this->assertFalse($this->bo->fresh()->hasReachedProductLimit());
        $this->assertTrue(Gate::forUser($this->user->fresh())->allows('create', Product::class));

        // View page at 19 products
        $response = $this->actingAs($this->user)->get(route('business.products.index'));
        $response->assertOk();
        $response->assertSee('Product Limit: 19 / 20 Products');
        $response->assertSee('1 Slots Remaining');

        // Propose the 20th product successfully
        $propResponse = $this->actingAs($this->user)->post(route('business.products.propose'), [
            'name' => 'Final Product 20',
            'customer_price' => 25.00,
            'cost_price' => 15.00,
            'description' => '20th product',
        ]);

        $propResponse->assertRedirect(route('business.products.index'));
        $this->assertSame(20, $this->bo->fresh()->productCount());
        $this->assertTrue($this->bo->fresh()->hasReachedProductLimit());
    }

    public function test_business_owner_cannot_exceed_20_products_via_store(): void
    {
        // Populate 20 products
        for ($i = 1; $i <= 20; $i++) {
            Product::query()->create([
                'business_owner_id' => $this->bo->id,
                'product_no' => sprintf('PRD%06d', $i),
                'name' => "Product {$i}",
                'selling_price' => 10.00,
                'status' => ProductStatus::Active,
                'created_by' => $this->user->id,
            ]);
        }

        // Web redirect attempt for 21st product
        $response = $this->actingAs($this->user)->post(route('business.products.store'), [
            'name' => 'Product 21',
            'selling_price' => 50.00,
            'stock_quantity' => 10,
        ]);

        $response->assertRedirect(route('business.products.index'));
        $response->assertSessionHas('error');
        $this->assertSame(20, $this->bo->fresh()->productCount());

        // JSON API attempt for 21st product returns 422
        $jsonResponse = $this->actingAs($this->user)->postJson(route('business.products.store'), [
            'name' => 'Product 21 JSON',
            'selling_price' => 50.00,
            'stock_quantity' => 10,
        ]);

        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJson([
            'success' => false,
            'message' => 'You have reached the maximum limit of 20 products per Business Owner.',
        ]);
        $this->assertSame(20, $this->bo->fresh()->productCount());
    }

    public function test_business_owner_cannot_exceed_20_products_via_propose(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Product::query()->create([
                'business_owner_id' => $this->bo->id,
                'product_no' => sprintf('PRD%06d', $i),
                'name' => "Product {$i}",
                'selling_price' => 10.00,
                'status' => ProductStatus::Active,
                'created_by' => $this->user->id,
            ]);
        }

        // Propose attempt for 21st product via web redirect
        $response = $this->actingAs($this->user)->post(route('business.products.propose'), [
            'name' => 'Product 21 Propose',
            'customer_price' => 30.00,
            'cost_price' => 20.00,
        ]);

        $response->assertRedirect(route('business.products.index'));
        $response->assertSessionHas('error');
        $this->assertSame(20, $this->bo->fresh()->productCount());

        // Propose attempt via JSON
        $jsonResponse = $this->actingAs($this->user)->postJson(route('business.products.propose'), [
            'name' => 'Product 21 Propose JSON',
            'customer_price' => 30.00,
            'cost_price' => 20.00,
        ]);

        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJson([
            'success' => false,
            'message' => 'You have reached the maximum limit of 20 products per Business Owner.',
        ]);
        $this->assertSame(20, $this->bo->fresh()->productCount());
    }

    public function test_product_policy_denies_create_when_bo_reaches_limit(): void
    {
        $this->assertTrue(Gate::forUser($this->user->fresh())->allows('create', Product::class));

        for ($i = 1; $i <= 20; $i++) {
            Product::query()->create([
                'business_owner_id' => $this->bo->id,
                'product_no' => sprintf('PRD%06d', $i),
                'name' => "Product {$i}",
                'selling_price' => 10.00,
                'status' => ProductStatus::Active,
                'created_by' => $this->user->id,
            ]);
        }

        $this->assertFalse(Gate::forUser($this->user->fresh())->allows('create', Product::class));
    }

    public function test_products_page_displays_limit_reached_and_disabled_button(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Product::query()->create([
                'business_owner_id' => $this->bo->id,
                'product_no' => sprintf('PRD%06d', $i),
                'name' => "Product {$i}",
                'selling_price' => 10.00,
                'status' => ProductStatus::Active,
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('business.products.index'));

        $response->assertOk();
        $response->assertSee('Limit Reached (20/20)');
        $response->assertSee('Product Limit: 20 / 20 Products');
        $response->assertSee('New Product (Limit Reached)');
    }

    public function test_filament_create_product_mutation_validates_limit(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Product::query()->create([
                'business_owner_id' => $this->bo->id,
                'product_no' => sprintf('PRD%06d', $i),
                'name' => "Product {$i}",
                'selling_price' => 10.00,
                'status' => ProductStatus::Active,
                'created_by' => $this->user->id,
            ]);
        }

        $this->actingAs($this->user);

        $this->expectException(ValidationException::class);

        $reflection = new \ReflectionMethod(CreateProduct::class, 'mutateFormDataBeforeCreate');
        $reflection->setAccessible(true);
        $reflection->invoke(new CreateProduct, [
            'name' => 'Over Limit Product',
            'selling_price' => 50,
            'status' => ProductStatus::Active->value,
        ]);
    }
}
