<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\ManualOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cast_strips_symbols_on_any_write_path(): void
    {
        $partner = Partner::factory()->create(['hp_no' => '012-345 6789', 'spouse_hp_no' => '+60 19 876 5432']);
        $bo = BusinessOwner::factory()->forPartner($partner)->create(['hp_no' => '(013) 111-2222']);
        $vendor = Vendor::query()->create(['name' => 'Kilang', 'phone' => '011 2233 4455', 'status' => 'active']);

        // All stored as canonical, symbol-free wa.me digits.
        $this->assertSame('60123456789', $partner->fresh()->hp_no);
        $this->assertSame('60198765432', $partner->fresh()->spouse_hp_no);
        $this->assertSame('60131112222', $bo->fresh()->hp_no);
        $this->assertSame('601122334455', $vendor->fresh()->phone);
    }

    public function test_customer_hp_no_and_lookup_key_are_normalized(): void
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->forPartner($partner)->create();

        $customer = Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_no' => 'C-NORM-1',
            'name' => 'Symbol Phone',
            'hp_no' => '012-345 6789',
        ]);

        $fresh = $customer->fresh();
        $this->assertSame('60123456789', $fresh->hp_no);
        $this->assertSame('60123456789', $fresh->hp_no_normalized);
    }

    public function test_a_blank_phone_casts_to_null(): void
    {
        $partner = Partner::factory()->create(['hp_no' => '']);

        $this->assertNull($partner->fresh()->hp_no);
    }

    public function test_public_order_matches_the_same_buyer_regardless_of_phone_formatting(): void
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->forPartner($partner)->create();
        $product = Product::factory()->forBusinessOwner($bo)->create([
            'status' => ProductStatus::Active,
        ]);

        $service = app(ManualOrderService::class);

        $first = $service->placePublicOrder($product, [
            'name' => 'Buyer', 'phone' => '012-345 6789', 'quantity' => 1,
        ]);
        // Same number, different formatting → same customer, no duplicate Lead.
        $second = $service->placePublicOrder($product, [
            'name' => 'Buyer', 'phone' => '+60 12 3456789', 'quantity' => 2,
        ]);

        $this->assertSame($first->customer_id, $second->customer_id);
        $this->assertSame('60123456789', Customer::query()->find($first->customer_id)->hp_no);
        $this->assertSame(1, Customer::query()->where('business_owner_id', $bo->id)->count());
    }
}
