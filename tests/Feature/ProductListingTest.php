<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_product_form_exposes_only_essentials(): void
    {
        [, $boUser] = $this->businessOwnerUser('P001401', 'BO001401', 'Cards BO');

        $fields = $this->visibleProductFormFieldKeys($boUser);

        foreach (['name', 'selling_price', 'description', 'product_type_id', 'status', 'media_assets'] as $essential) {
            $this->assertContains($essential, $fields, "BO form should expose {$essential}");
        }

        foreach (['cost', 'vendor_id', 'sales_commission', 'territory_id'] as $adminOnly) {
            $this->assertNotContains($adminOnly, $fields, "BO form must hide {$adminOnly}");
        }
    }

    public function test_business_owner_crafted_save_cannot_set_admin_only_fields(): void
    {
        [, $boUser, $bo] = $this->businessOwnerUser('P001402', 'BO001402', 'Crafted BO');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Crafted',
                'selling_price' => 50,
                'status' => ProductStatus::Active->value,
            ])
            ->set('data.cost', 999)
            ->set('data.sales_commission', 77)
            ->set('data.vendor_id', 1)
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->firstWhere('name', 'Crafted');

        $this->assertNotNull($product);
        $this->assertSame($bo->id, $product->business_owner_id);
        $this->assertNull($product->cost);
        $this->assertSame('0.00', $product->sales_commission);
        $this->assertNull($product->vendor_id);
    }

    public function test_business_owner_cannot_reassign_product_to_another_owner_on_edit(): void
    {
        [, $boUserA, $boA] = $this->businessOwnerUser('P001407', 'BO001407', 'Owner A');
        [, , $boB] = $this->businessOwnerUser('P001408', 'BO001408', 'Owner B');

        $product = Product::query()->create([
            'product_no' => 'PRD1407',
            'business_owner_id' => $boA->id,
            'created_by' => $boUserA->id,
            'name' => 'Owner A Product',
            'selling_price' => 30,
            'status' => ProductStatus::Active,
        ]);

        $this->actingAs($boUserA);
        Filament::setCurrentPanel('business');

        // BO A edits their OWN product but injects another BO's id via a crafted
        // payload. The owner FK is immutable post-create and must NOT change.
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'name' => 'Owner A Product Renamed',
                'selling_price' => 31,
            ])
            ->set('data.business_owner_id', $boB->id)
            ->set('data.created_by', $boB->user_id)
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();

        $this->assertSame($boA->id, $product->business_owner_id);
        $this->assertSame($boUserA->id, $product->created_by);
        $this->assertSame('Owner A Product Renamed', $product->name);
    }

    public function test_business_owner_grid_lists_only_own_products(): void
    {
        [, $boUserA, $boA] = $this->businessOwnerUser('P001403', 'BO001403', 'Grid BO A');
        [, , $boB] = $this->businessOwnerUser('P001404', 'BO001404', 'Grid BO B');

        $alpha = Product::query()->create([
            'product_no' => 'PRD1401',
            'business_owner_id' => $boA->id,
            'name' => 'Alpha Soap',
            'selling_price' => 20,
            'status' => ProductStatus::Active,
        ]);
        $beta = Product::query()->create([
            'product_no' => 'PRD1402',
            'business_owner_id' => $boB->id,
            'name' => 'Beta Oil',
            'selling_price' => 30,
            'status' => ProductStatus::Active,
        ]);

        $this->actingAs($boUserA);
        Filament::setCurrentPanel('business');

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords(Product::query()->whereKey($alpha->id)->get())
            ->assertCanNotSeeTableRecords(Product::query()->whereKey($beta->id)->get())
            ->assertSee('Alpha Soap')
            ->assertDontSee('Beta Oil');
    }

    public function test_product_listing_exposes_category_and_status_filters(): void
    {
        [, $boUser] = $this->businessOwnerUser('P001405', 'BO001405', 'Filter BO');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(ListProducts::class)
            ->assertTableFilterExists('product_type_id')
            ->assertTableFilterExists('status');
    }

    public function test_business_owner_image_upload_blocks_svg_and_caps_size(): void
    {
        [, $boUser] = $this->businessOwnerUser('P001406', 'BO001406', 'Upload BO');

        $this->actingAs($boUser);

        $schema = ProductForm::configure(Schema::make(new CreateProduct));

        $field = $schema->getFlatFields(withHidden: false)['media_assets'];

        $this->assertInstanceOf(FileUpload::class, $field);
        $this->assertTrue(in_array('image/png', $field->getAcceptedFileTypes(), true));
        // DP-11: video is now accepted alongside images…
        $this->assertTrue(in_array('video/mp4', $field->getAcceptedFileTypes(), true));
        // …but arbitrary/unsafe types still are not, and the size is capped (20 MB).
        $this->assertFalse(in_array('image/svg+xml', $field->getAcceptedFileTypes(), true));
        $this->assertSame(20480, $field->getMaxSize());
    }

    public function test_created_product_defaults_to_active_and_is_publicly_visible(): void
    {
        [, $boUser, $bo] = $this->businessOwnerUser('P001490', 'BO001490', 'Default Status BO');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Auto Active Product',
                'selling_price' => 45,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->firstWhere('name', 'Auto Active Product');

        $this->assertNotNull($product);
        $this->assertSame(ProductStatus::Active, $product->status);
        $this->assertTrue($product->isPubliclyVisible());
    }

    public function test_admin_can_assign_business_owner_on_product_creation(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
        [, , $bo] = $this->businessOwnerUser('P001491', 'BO001491', 'Admin Assigned BO');

        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Admin Created Product',
                'selling_price' => 100,
                'business_owner_id' => $bo->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->firstWhere('name', 'Admin Created Product');

        $this->assertNotNull($product);
        $this->assertSame($bo->id, $product->business_owner_id);
        $this->assertTrue($product->isPubliclyVisible());
    }

    /**
     * @return list<string>
     */
    private function visibleProductFormFieldKeys(User $user): array
    {
        $this->actingAs($user);

        $schema = ProductForm::configure(Schema::make(new CreateProduct));

        return array_keys($schema->getFlatFields(withHidden: false));
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
}
