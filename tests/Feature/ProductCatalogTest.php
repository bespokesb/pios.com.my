<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_catalog_visibility_is_active_only(): void
    {
        [$partnerUser] = $this->partnerUser('P000701', 'Catalog Visibility Partner');
        $activeProduct = $this->product('PRD000701', 'Active Product', ProductStatus::Active);
        $draftProduct = $this->product('PRD000702', 'Draft Product', ProductStatus::Draft);
        $inactiveProduct = $this->product('PRD000703', 'Inactive Product', ProductStatus::Inactive);
        $archivedProduct = $this->product('PRD000704', 'Archived Product', ProductStatus::Archived);

        $this->actingAs($partnerUser);

        $visibleProductNos = ProductResource::getEloquentQuery()
            ->pluck('product_no')
            ->all();

        // Partners (non-owning) still see the shared catalog, active products only.
        $this->assertSame([$activeProduct->product_no], $visibleProductNos);
        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $activeProduct));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('view', $draftProduct));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('view', $inactiveProduct));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('view', $archivedProduct));
    }

    public function test_product_catalog_management_permissions(): void
    {
        $admin = $this->adminUser();
        [$partnerUser] = $this->partnerUser('P000705', 'Catalog Partner');
        [, $businessOwnerUser] = $this->businessOwnerUser('P000706', 'BO000706', 'Catalog BO');
        // Admin-owned catalog product (no Business Owner).
        $product = $this->product('PRD000705', 'Managed Product', ProductStatus::Active);

        $this->assertTrue(Gate::forUser($admin)->allows('create', Product::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $product));
        // Partners never create or manage products.
        $this->assertFalse(Gate::forUser($partnerUser)->allows('create', Product::class));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('update', $product));
        // Business Owners can create their own, but cannot edit an admin/other-owned product.
        $this->assertTrue(Gate::forUser($businessOwnerUser)->allows('create', Product::class));
        $this->assertFalse(Gate::forUser($businessOwnerUser)->allows('update', $product));
    }

    public function test_business_owner_creates_and_only_sees_own_products(): void
    {
        [, $boUserA, $boA] = $this->businessOwnerUser('P000710', 'BO000710', 'Products BO A');
        [, $boUserB, $boB] = $this->businessOwnerUser('P000711', 'BO000711', 'Products BO B');

        $this->actingAs($boUserA);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'status' => ProductStatus::Active->value,
                'name' => 'BO A Product',
                'selling_price' => 25,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->firstWhere('name', 'BO A Product');

        $this->assertNotNull($product);
        $this->assertSame($boA->id, $product->business_owner_id);

        // Hidden admin-only fields fall back to safe server-side defaults:
        // a BO product carries no commission.
        $this->assertSame('0.00', $product->sales_commission);

        // Owner sees their product; the other Business Owner does not.
        $this->assertTrue(Product::query()->visibleTo($boUserA->fresh())->whereKey($product->id)->exists());
        $this->assertFalse(Product::query()->visibleTo($boUserB->fresh())->whereKey($product->id)->exists());

        // The other Business Owner cannot edit it.
        $this->assertTrue(Gate::forUser($boUserA->fresh())->allows('update', $product));
        $this->assertFalse(Gate::forUser($boUserB->fresh())->allows('update', $product));
    }

    public function test_product_admin_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/products', $routes);
        $this->assertContains('admin/products/create', $routes);
        $this->assertContains('admin/products/{record}/edit', $routes);
    }

    private function product(string $productNo, string $name, ProductStatus $status): Product
    {
        return Product::query()->create([
            'product_no' => $productNo,
            'name' => $name,
            'status' => $status,
        ]);
    }

    /**
     * @return array{0: User, 1: Partner}
     */
    private function partnerUser(string $partnerNo, string $name): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'user_id' => $user->id,
            'partner_no' => $partnerNo,
            'applicant_name' => $name,
        ]);

        return [$user, $partner];
    }

    /**
     * @return array{0: Partner, 1: User, 2: BusinessOwner}
     */
    private function businessOwnerUser(string $partnerNo, string $businessOwnerNo, string $name): array
    {
        $user = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => "{$name} Partner",
        ]);
        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => $name,
        ]);

        return [$partner, $user, $businessOwner];
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
