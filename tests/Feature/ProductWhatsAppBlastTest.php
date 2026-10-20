<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProductWhatsAppBlastTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_blasts_own_product_to_own_customers(): void
    {
        config()->set('services.ustazai.base_url', 'https://ustazai.my/api');
        config()->set('services.ustazai.endpoint', '/send');
        Http::fake(['ustazai.my/*' => Http::response(['status' => 'ok'], 200)]);

        [$partner, $boUser, $bo] = $this->businessOwner('P001601', 'BO001601');
        [, , $otherBo] = $this->businessOwner('P001602', 'BO001602');

        // This Business Owner's own UstazAI key enables blasting.
        $bo->update(['ustazai_api_key' => 'test-key', 'ustazai_sender' => 'device-1']);

        $product = $this->product($bo, 'PRD1601', 'Gamat Oil', 39.90);
        $this->customer($bo, $partner->id, 'C001601', '0123456789');
        $this->customer($bo, $partner->id, 'C001602', '0198887777');
        // Another BO's customer must NOT receive the blast.
        $this->customer($otherBo, $otherBo->partner_id, 'C001603', '0111112222');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(ListProducts::class)
            ->callTableAction('blast', $product, data: ['segment' => 'all']);

        Http::assertSent(function ($request): bool {
            $recipients = $request['recipients'] ?? [];

            return str_contains($request->url(), 'ustazai.my')
                && in_array('60123456789', $recipients, true)
                && in_array('60198887777', $recipients, true)
                && ! in_array('60111112222', $recipients, true)   // other BO excluded
                && str_contains($request['message'] ?? '', 'Gamat Oil');
        });
    }

    public function test_blast_sends_nothing_when_not_configured(): void
    {
        Http::fake();

        // No ustazai_api_key on this Business Owner → nothing is sent.
        [$partner, $boUser, $bo] = $this->businessOwner('P001603', 'BO001603');
        $product = $this->product($bo, 'PRD1602', 'No Config Product', 10);
        $this->customer($bo, $partner->id, 'C001604', '0123456789');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(ListProducts::class)
            ->callTableAction('blast', $product, data: ['segment' => 'all']);

        Http::assertNothingSent();
    }

    /** @return array{0: Partner, 1: User, 2: BusinessOwner} */
    private function businessOwner(string $partnerNo, string $boNo): array
    {
        $user = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $partner = Partner::query()->create(['partner_no' => $partnerNo, 'applicant_name' => 'P '.$partnerNo]);
        $bo = BusinessOwner::query()->create([
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $boNo,
            'applicant_name' => 'BO '.$boNo,
        ]);

        return [$partner, $user->fresh(), $bo];
    }

    private function product(BusinessOwner $bo, string $no, string $name, float $price): Product
    {
        return Product::query()->create([
            'product_no' => $no,
            'business_owner_id' => $bo->id,
            'name' => $name,
            'selling_price' => $price,
            'status' => ProductStatus::Active,
        ]);
    }

    private function customer(BusinessOwner $bo, int $partnerId, string $no, string $phone): Customer
    {
        return Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $partnerId,
            'customer_no' => $no,
            'name' => 'Cust '.$no,
            'hp_no' => $phone,
            'category' => CustomerCategory::Customer,
        ]);
    }
}
