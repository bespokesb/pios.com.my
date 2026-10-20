<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ProductShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_url_contains_name_and_price_rawurlencoded(): void
    {
        [, , $bo] = $this->businessOwnerUser('P150001', 'BO150001', 'Share BO');
        $product = $this->ownedProduct($bo, 'PRD150001', 'Manuka Honey');

        $url = $product->shareWhatsAppUrl();

        $this->assertStringStartsWith('https://wa.me/?text=', $url);
        $this->assertStringContainsString(rawurlencode('Manuka Honey'), $url);
        $this->assertStringContainsString('25.00', $url);
        $this->assertStringContainsString(rawurlencode('Cold-pressed organic blend'), $url);
    }

    public function test_share_url_never_contains_cost_vendor_or_commission(): void
    {
        [, , $bo] = $this->businessOwnerUser('P150002', 'BO150002', 'No Leak BO');
        $product = $this->ownedProduct($bo, 'PRD150005', 'Sealed Product');

        $url = $product->shareWhatsAppUrl();

        $this->assertStringNotContainsString('99.99', $url);            // cost
        $this->assertStringNotContainsString('7.77', $url);             // sales_commission
        $this->assertStringNotContainsString('cost', strtolower(rawurldecode($url)));
    }

    public function test_first_shareable_image_url_resolves_public_disk_or_null(): void
    {
        [, , $bo] = $this->businessOwnerUser('P150003', 'BO150003', 'Image BO');

        $withImage = $this->ownedProduct($bo, 'PRD150002', 'With Image');
        $withImage->update(['media_assets' => ['products/honey.jpg']]);
        $this->assertNotNull($withImage->firstShareableImageUrl());
        $this->assertStringContainsString('products/honey.jpg', $withImage->firstShareableImageUrl());

        $noImage = $this->ownedProduct($bo, 'PRD150003', 'No Image');
        $noImage->update(['media_assets' => []]);
        $this->assertNull($noImage->firstShareableImageUrl());
    }

    public function test_share_policy_allows_owner_denies_other_bo_and_admin(): void
    {
        [, $boUser, $bo] = $this->businessOwnerUser('P150010', 'BO150010', 'Owner BO');
        [, $otherBoUser] = $this->businessOwnerUser('P150011', 'BO150011', 'Other BO');
        $admin = $this->adminUser();

        $product = $this->ownedProduct($bo, 'PRD150010', 'Gated Product');

        $this->assertTrue(Gate::forUser($boUser->fresh())->allows('share', $product));
        $this->assertFalse(Gate::forUser($otherBoUser->fresh())->allows('share', $product));
        $this->assertFalse(Gate::forUser($admin)->allows('share', $product)); // admin doesn't own -> no share
    }

    public function test_share_action_visible_only_on_own_product_in_listing(): void
    {
        [, $boUser, $bo] = $this->businessOwnerUser('P150020', 'BO150020', 'Listing BO');
        $product = $this->ownedProduct($bo, 'PRD150020', 'Listed Product');

        $this->actingAs($boUser->fresh());
        Livewire::test(ListProducts::class)
            ->assertTableActionVisible('share', $product->getKey());

        $this->actingAs($this->adminUser());
        Livewire::test(ListProducts::class)
            ->assertTableActionHidden('share', $product->getKey());
    }

    public function test_table_money_columns_render_as_ringgit(): void
    {
        [, , $bo] = $this->businessOwnerUser('P150099', 'BO150099', 'Currency BO');
        $this->ownedProduct($bo, 'PRD150099', 'Priced Item'); // selling_price 25

        $this->actingAs($this->adminUser());

        // ICU formats MYR with a non-breaking space: "RM<nbsp>25.00".
        Livewire::test(ListProducts::class)
            ->assertSee("RM\u{00A0}25.00")
            ->assertDontSee('MYR')
            ->assertDontSee('USD');
    }

    private function ownedProduct(BusinessOwner $bo, string $no, string $name): Product
    {
        return Product::query()->create([
            'business_owner_id' => $bo->id,
            'product_no' => $no,
            'name' => $name,
            'description' => 'Cold-pressed organic blend',
            'selling_price' => 25,
            'cost' => 99.99,
            'sales_commission' => 7.77,
            'status' => ProductStatus::Active,
        ]);
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
