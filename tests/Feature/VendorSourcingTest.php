<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class VendorSourcingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_form_exposes_cost_and_vendor(): void
    {
        // Admin can mount the create page; the authoritative Livewire form
        // assertion confirms cost + vendor are present for HQ.
        $this->actingAs($this->adminUser());
        Filament::setCurrentPanel('admin');

        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('cost')
            ->assertFormFieldExists('vendor_id');

        // And the schema itself exposes the visible fields for an admin.
        $fields = $this->visibleProductFormFieldKeys($this->adminUser());
        $this->assertContains('cost', $fields);
        $this->assertContains('vendor_id', $fields);
    }

    public function test_business_owner_product_form_hides_cost_and_vendor(): void
    {
        [, $businessOwnerUser] = $this->businessOwnerUser('P000801', 'BO000801', 'Sourcing BO');

        // A BusinessOwner cannot create products (admin-only policy), so the
        // create page is not mountable for them. The column-level guarantee is
        // proven at the schema level: ->visible(isAdmin()) removes the field.
        $fields = $this->visibleProductFormFieldKeys($businessOwnerUser);

        $this->assertNotContains('cost', $fields);
        $this->assertNotContains('vendor_id', $fields);
    }

    public function test_partner_product_form_hides_cost_and_vendor(): void
    {
        [$partnerUser] = $this->partnerUser('P000802', 'Sourcing Partner');

        $fields = $this->visibleProductFormFieldKeys($partnerUser);

        $this->assertNotContains('cost', $fields);
        $this->assertNotContains('vendor_id', $fields);
    }

    public function test_business_owner_cannot_set_product_cost_via_form(): void
    {
        [, $businessOwnerUser] = $this->businessOwnerUser('P000803', 'BO000803', 'Uncostable BO');

        // The cost/vendor fields are absent from the schema for a BO, so they
        // cannot be submitted: a non-admin can never set a product's cost.
        $fields = $this->visibleProductFormFieldKeys($businessOwnerUser);
        $this->assertNotContains('cost', $fields);
        $this->assertNotContains('vendor_id', $fields);

        // Confirm a product created without a cost persists a null cost.
        $product = $this->product('PRD000803', 'No Cost Product', ProductStatus::Active);
        $this->assertNull($product->cost);
    }

    /**
     * Resolve the dehydratable (visible, non-hidden) field keys of the shared
     * ProductForm as evaluated for a specific user. ->visible(false) fields are
     * excluded by getFlatFields(withHidden: false), which is exactly the
     * column-level (absent + non-dehydrated) guarantee under test.
     *
     * @return list<string>
     */
    private function visibleProductFormFieldKeys(User $user): array
    {
        $this->actingAs($user);

        $schema = ProductForm::configure(
            Schema::make(new CreateProduct)
        );

        return array_keys($schema->getFlatFields(withHidden: false));
    }

    public function test_cost_and_margin_are_hidden_from_business_owner_table_but_visible_to_admin(): void
    {
        [, $businessOwnerUser] = $this->businessOwnerUser('P000804', 'BO000804', 'Table Isolation BO');

        Product::query()->create([
            'product_no' => 'PRD000804',
            'name' => 'Sourced Product',
            'selling_price' => 100,
            'cost' => 40,
            'status' => ProductStatus::Active,
        ]);

        // Business Owner: cost (40.00) and margin (60.00) must not render.
        $this->actingAs($businessOwnerUser);
        Filament::setCurrentPanel('business');

        Livewire::test(ListProducts::class)
            ->assertDontSee('40.00')
            ->assertDontSee('60.00');

        // Admin: margin (60.00) is visible.
        $this->actingAs($this->adminUser());
        Filament::setCurrentPanel('admin');

        Livewire::test(ListProducts::class)
            ->assertSee('60.00');
    }

    public function test_vendor_resource_is_admin_only(): void
    {
        $admin = $this->adminUser();
        [$partnerUser] = $this->partnerUser('P000805', 'Vendor Policy Partner');
        [, $businessOwnerUser] = $this->businessOwnerUser('P000806', 'BO000806', 'Vendor Policy BO');

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Vendor::class));
        $this->assertTrue(Gate::forUser($businessOwnerUser)->denies('viewAny', Vendor::class));
        $this->assertTrue(Gate::forUser($partnerUser)->denies('viewAny', Vendor::class));
    }

    public function test_vendor_routes_are_admin_only(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/vendors', $routes);
        $this->assertNotContains('business/vendors', $routes);
        $this->assertNotContains('partner/vendors', $routes);
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

    public function test_admin_can_register_a_vendor_and_it_is_audited_with_a_normalised_phone(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(CreateVendor::class)
            ->fillForm([
                'name' => 'Kilang Madu Sdn Bhd',
                'contact_name' => 'Encik Ali',
                'phone' => '12-345 6789',
                'email' => 'sales@kilangmadu.test',
                'status' => RecordStatus::Active->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $vendor = Vendor::query()->firstWhere('name', 'Kilang Madu Sdn Bhd');

        $this->assertNotNull($vendor);
        // Phone stored normalised with the +60 country code.
        $this->assertSame('60123456789', $vendor->phone);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => Vendor::class,
            'auditable_id' => $vendor->id,
            'event' => 'created',
        ]);
    }

    public function test_a_business_owner_cannot_access_the_vendor_register(): void
    {
        [, $boUser] = $this->businessOwnerUser('P000841', 'BO000841', 'No Vendor BO');

        $this->assertFalse(Gate::forUser($boUser)->allows('viewAny', Vendor::class));
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
