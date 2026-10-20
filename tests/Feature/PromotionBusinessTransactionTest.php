<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Enums\PromotionMode;
use App\Enums\PromotionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Promotions\Pages\CreatePromotion;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class PromotionBusinessTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_run_manual_promotion_for_own_customers(): void
    {
        [$businessOwnerUser, $partner, $businessOwner] = $this->businessOwnerUser('P001101', 'BO001101', 'Promo BO');
        $product = $this->product('PRD001101', 'Promo Product', 40);
        $customer = $this->customer($businessOwner, $partner, 'C001101', CustomerCategory::Customer);
        $this->selectProduct($businessOwner, $product);

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'mode' => PromotionMode::Manual->value,
                'product_id' => $product->id,
                'discount_type' => 'amount',
                'discount_value' => 5,
                'offer_terms' => 'Manual offer terms',
                'customer_ids' => [$customer->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $promotion = Promotion::query()->with('customers')->first();

        $this->assertNotNull($promotion);
        $this->assertSame($businessOwner->id, $promotion->business_owner_id);
        $this->assertSame($partner->id, $promotion->partner_id);
        $this->assertSame(PromotionMode::Manual, $promotion->mode);
        $this->assertSame(PromotionStatus::Running, $promotion->status);
        $this->assertNotNull($promotion->ran_at);
        $this->assertSame([$customer->id], $promotion->customers->pluck('id')->all());
        $this->assertDatabaseHas('promotion_customers', [
            'promotion_id' => $promotion->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => Promotion::class,
            'auditable_id' => $promotion->id,
            'event' => 'created',
        ]);
    }

    public function test_auto_promotion_requires_schedule_and_is_scheduled(): void
    {
        [$businessOwnerUser, $partner, $businessOwner] = $this->businessOwnerUser('P001103', 'BO001103', 'Auto Promo BO');
        $product = $this->product('PRD001103', 'Auto Promo Product', 65);
        $customer = $this->customer($businessOwner, $partner, 'C001103', CustomerCategory::Customer);
        $this->selectProduct($businessOwner, $product);

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'mode' => PromotionMode::Auto->value,
                'product_id' => $product->id,
                'customer_ids' => [$customer->id],
            ])
            ->call('create')
            ->assertHasFormErrors(['starts_at', 'ends_at']);

        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'mode' => PromotionMode::Auto->value,
                'starts_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'product_id' => $product->id,
                'discount_type' => 'percent',
                'discount_value' => 10,
                'customer_ids' => [$customer->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $promotion = Promotion::query()->latest('id')->first();

        $this->assertNotNull($promotion);
        $this->assertSame(PromotionMode::Auto, $promotion->mode);
        $this->assertSame(PromotionStatus::Scheduled, $promotion->status);
        $this->assertNull($promotion->ran_at);
        $this->assertDatabaseHas('promotion_customers', [
            'promotion_id' => $promotion->id,
            'customer_id' => $customer->id,
            'status' => 'selected',
        ]);
    }

    public function test_promotion_blocks_unselected_other_owner_and_ineligible_customers(): void
    {
        [$businessOwnerUser, $partner, $businessOwner] = $this->businessOwnerUser('P001104', 'BO001104', 'Blocked Promo BO');
        [$otherPartner, $otherBusinessOwner] = $this->partnerAndBusinessOwner('P001105', 'BO001105', 'Other Blocked BO');
        $ownVipCustomer = $this->customer($businessOwner, $partner, 'C001104', CustomerCategory::Customer, true);
        $otherCustomer = $this->customer($otherBusinessOwner, $otherPartner, 'C001105', CustomerCategory::Customer);
        $unselectedProduct = $this->product('PRD001104', 'Unselected Promo Product', 30);
        $selectedProduct = $this->product('PRD001105', 'Selected Promo Product', 35);
        $this->selectProduct($businessOwner, $selectedProduct);

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'mode' => PromotionMode::Manual->value,
                'product_id' => $unselectedProduct->id,
                'customer_ids' => [$ownVipCustomer->id],
            ])
            ->call('create')
            ->assertHasFormErrors(['product_id']);

        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'mode' => PromotionMode::Manual->value,
                'product_id' => $selectedProduct->id,
                'customer_ids' => [$otherCustomer->id],
            ])
            ->call('create')
            ->assertHasFormErrors(['customer_ids.0']);

        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'mode' => PromotionMode::Manual->value,
                'product_id' => $selectedProduct->id,
                'discount_type' => 'amount',
                'discount_value' => 5,
                'offer_terms' => 'VIP promo terms',
                'customer_ids' => [$ownVipCustomer->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_partner_can_view_downstream_promotion_but_not_create_or_update(): void
    {
        [$partnerUser, $partner] = $this->partnerUser('P001106', 'Promotion Partner');
        $businessOwner = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO001106',
            'applicant_name' => 'Downstream Promotion BO',
        ]);
        $product = $this->product('PRD001106', 'Downstream Promo Product', 20);
        $customer = $this->customer($businessOwner, $partner, 'C001106', CustomerCategory::Customer);
        $promotion = Promotion::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'mode' => PromotionMode::Manual,
            'status' => PromotionStatus::Running,
        ]);
        $promotion->customers()->sync([$customer->id => ['status' => 'sent']]);

        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $promotion));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('create', Promotion::class));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('update', $promotion));
    }

    public function test_business_transaction_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/business-transactions', $routes);
        $this->assertContains('admin/business-transactions/create', $routes);
    }

    private function product(string $productNo, string $name, int $price): Product
    {
        return Product::query()->create([
            'product_no' => $productNo,
            'name' => $name,
            'selling_price' => $price,
            'status' => ProductStatus::Active,
        ]);
    }

    private function selectProduct(BusinessOwner $businessOwner, Product $product): void
    {
        BusinessOwnerProductSelection::query()->create([
            'business_owner_id' => $businessOwner->id,
            'product_id' => $product->id,
            'status' => ProductSelectionStatus::Selected,
            'selected_at' => now(),
        ]);
    }

    private function customer(
        BusinessOwner $businessOwner,
        Partner $partner,
        string $customerNo,
        CustomerCategory $category,
        bool $isVip = false
    ): Customer {
        return Customer::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_no' => $customerNo,
            'category' => $category,
            'is_vip' => $isVip,
            'name' => "{$customerNo} Customer",
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
     * @return array{0: User, 1: Partner, 2: BusinessOwner}
     */
    private function businessOwnerUser(string $partnerNo, string $businessOwnerNo, string $name): array
    {
        $user = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        [$partner, $businessOwner] = $this->partnerAndBusinessOwner($partnerNo, $businessOwnerNo, $name, $user);

        return [$user, $partner, $businessOwner];
    }

    /**
     * @return array{0: Partner, 1: BusinessOwner}
     */
    private function partnerAndBusinessOwner(string $partnerNo, string $businessOwnerNo, string $name, ?User $user = null): array
    {
        $partner = Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => "{$name} Partner",
        ]);
        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $user?->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => $name,
        ]);

        return [$partner, $businessOwner];
    }
}
