<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Fees\Pages\CreateFee;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Customer;
use App\Models\Fee;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CommerceAndFeesTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_create_order_for_own_customer_with_selected_eligible_product(): void
    {
        [$businessOwnerUser, $partner, $businessOwner] = $this->businessOwnerUser('P000901', 'BO000901', 'Order BO');
        [, , $otherBusinessOwner] = $this->businessOwnerUser('P000902', 'BO000902', 'Other Order BO');
        $customer = $this->customer($businessOwner, $partner, 'C000901', CustomerCategory::Customer);
        $product = $this->product('PRD000901', 'Selected Product', 50);
        $this->selectProduct($businessOwner, $product);

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'business_owner_id' => $otherBusinessOwner->id,
                'customer_id' => $customer->id,
                'status' => OrderStatus::Submitted->value,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::query()->with('items')->firstWhere('customer_id', $customer->id);

        $this->assertNotNull($order);
        $this->assertSame($businessOwner->id, $order->business_owner_id);
        $this->assertSame($partner->id, $order->partner_id);
        $this->assertSame('ORD000001', $order->order_no);
        $this->assertSame('100.00', $order->subtotal);
        $this->assertSame('100.00', $order->total);
        $this->assertNotNull($order->ordered_at);
        $this->assertCount(1, $order->items);
        $this->assertSame('Selected Product', $order->items->first()->product_name);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame('50.00', $order->items->first()->unit_price);
        $this->assertSame('100.00', $order->items->first()->line_total);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'event' => 'created',
        ]);
    }

    public function test_order_blocks_other_owner_customer_and_unselected_product(): void
    {
        [$businessOwnerUser, $partner, $businessOwner] = $this->businessOwnerUser('P000903', 'BO000903', 'Eligibility BO');
        [$otherPartner, $otherBusinessOwner] = $this->partnerAndBusinessOwner('P000904', 'BO000904', 'Other Customer BO');
        $ownVipCustomer = $this->customer($businessOwner, $partner, 'C000902', CustomerCategory::Customer, true);
        $otherCustomer = $this->customer($otherBusinessOwner, $otherPartner, 'C000903', CustomerCategory::Customer);
        $unselectedProduct = $this->product('PRD000902', 'Unselected Product', 25);
        $selectedProduct = $this->product('PRD000903', 'Selected Product', 30);
        $this->selectProduct($businessOwner, $selectedProduct);

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'customer_id' => $otherCustomer->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $selectedProduct->id, 'quantity' => 1],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['customer_id']);

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'customer_id' => $ownVipCustomer->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $unselectedProduct->id, 'quantity' => 1],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['items.0.product_id']);

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'customer_id' => $ownVipCustomer->id,
                'status' => OrderStatus::Draft->value,
                'items' => [
                    ['product_id' => $selectedProduct->id, 'quantity' => 1],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_partner_can_view_downstream_order_but_not_modify_it(): void
    {
        [$partnerUser, $partner] = $this->partnerUser('P000905', 'Order Partner');
        $businessOwner = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000905',
            'applicant_name' => 'Downstream Order BO',
        ]);
        $customer = $this->customer($businessOwner, $partner, 'C000905', CustomerCategory::Customer);
        $order = Order::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_id' => $customer->id,
            'order_no' => 'ORD000905',
            'status' => OrderStatus::Draft,
        ]);

        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $order));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('create', Order::class));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('update', $order));
    }

    public function test_admin_can_create_monthly_business_owner_fee_and_manual_payment(): void
    {
        $admin = $this->adminUser();
        [$partner, $businessOwner] = $this->partnerAndBusinessOwner('P000906', 'BO000906', 'Fee BO');

        $this->actingAs($admin);

        Livewire::test(CreateFee::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'fee_type' => FeeType::BusinessOwnerMonthly->value,
                'amount' => 99,
                'due_date' => '2026-06-30',
                'status' => FeeStatus::Unpaid->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $fee = Fee::query()->firstWhere('business_owner_id', $businessOwner->id);

        $this->assertNotNull($fee);
        $this->assertSame($partner->id, $fee->partner_id);
        $this->assertSame('99.00', $fee->amount);

        Livewire::test(CreatePayment::class)
            ->fillForm([
                'fee_id' => $fee->id,
                'payment_type' => 'manual',
                'amount' => 99,
                'status' => PaymentStatus::Verified->value,
                'reference_no' => 'PAY-REF-001',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $payment = Payment::query()->firstWhere('reference_no', 'PAY-REF-001');

        $this->assertNotNull($payment);
        $this->assertSame($fee->id, $payment->fee_id);
        $this->assertNull($payment->order_id);
        $this->assertSame($admin->id, $payment->recorded_by);
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_payment_requires_exactly_one_fee_or_order_target(): void
    {
        $admin = $this->adminUser();
        [$partner, $businessOwner] = $this->partnerAndBusinessOwner('P000907', 'BO000907', 'Payment Target BO');
        $customer = $this->customer($businessOwner, $partner, 'C000907', CustomerCategory::Customer);
        $fee = Fee::query()->create([
            'partner_id' => $partner->id,
            'business_owner_id' => $businessOwner->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'amount' => 50,
            'status' => FeeStatus::Unpaid,
        ]);
        $order = Order::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_id' => $customer->id,
            'order_no' => 'ORD000907',
            'status' => OrderStatus::Submitted,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreatePayment::class)
            ->fillForm([
                'fee_id' => $fee->id,
                'order_id' => $order->id,
                'payment_type' => 'manual',
                'amount' => 50,
                'status' => PaymentStatus::Pending->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['fee_id', 'order_id']);

        Livewire::test(CreatePayment::class)
            ->fillForm([
                'payment_type' => 'manual',
                'amount' => 50,
                'status' => PaymentStatus::Pending->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['fee_id', 'order_id']);
    }

    public function test_fee_and_payment_visibility_is_read_only_for_hierarchy_users(): void
    {
        [$partnerUser, $partner] = $this->partnerUser('P000908', 'Fee Partner');
        $businessOwnerUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $businessOwnerUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000908',
            'applicant_name' => 'Fee Read BO',
        ]);
        $fee = Fee::query()->create([
            'partner_id' => $partner->id,
            'business_owner_id' => $businessOwner->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'amount' => 50,
            'status' => FeeStatus::Unpaid,
        ]);
        $payment = Payment::query()->create([
            'fee_id' => $fee->id,
            'payment_type' => 'manual',
            'amount' => 50,
            'status' => PaymentStatus::Pending,
        ]);

        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $fee));
        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $payment));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('create', Fee::class));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('create', Payment::class));
        $this->assertTrue(Gate::forUser($businessOwnerUser)->allows('view', $fee));
        $this->assertTrue(Gate::forUser($businessOwnerUser)->allows('view', $payment));
        $this->assertFalse(Gate::forUser($businessOwnerUser)->allows('create', Fee::class));
        $this->assertFalse(Gate::forUser($businessOwnerUser)->allows('create', Payment::class));
    }

    public function test_commerce_fee_and_payment_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/orders', $routes);
        $this->assertContains('admin/orders/create', $routes);
        $this->assertContains('admin/fees', $routes);
        $this->assertContains('admin/fees/create', $routes);
        $this->assertContains('admin/payments', $routes);
        $this->assertContains('admin/payments/create', $routes);
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

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
