<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTransactionStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_advance_submitted_order_to_paid(): void
    {
        [$user, $partner, $bo] = $this->businessOwnerUser('P00901', 'BO00901', 'Test BO');
        $customer = $this->customer($bo, $partner, 'C00901');
        $order = $this->createOrder($bo, $partner, $customer, 'ORD-00901', OrderStatus::Submitted);

        $response = $this->actingAs($user)->postJson(route('business.transactions.status', $order), [
            'status' => 'paid',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'status' => 'paid',
                    'status_label' => 'Paid',
                ],
            ]);

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Paid, $fresh->status);
        $this->assertNotNull($fresh->paid_at);

        $this->assertDatabaseHas(AuditLog::class, [
            'actor_id' => $user->id,
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'event' => 'order_status_updated',
        ]);
    }

    public function test_business_owner_can_advance_paid_order_to_packed(): void
    {
        [$user, $partner, $bo] = $this->businessOwnerUser('P00902', 'BO00902', 'Test BO');
        $customer = $this->customer($bo, $partner, 'C00902');
        $order = $this->createOrder($bo, $partner, $customer, 'ORD-00902', OrderStatus::Paid);

        $response = $this->actingAs($user)->postJson(route('business.transactions.status', $order), [
            'status' => 'packed',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'status' => 'packed',
                ],
            ]);

        $this->assertSame(OrderStatus::Packed, $order->fresh()->status);
    }

    public function test_advancing_to_shipped_requires_tracking_number(): void
    {
        [$user, $partner, $bo] = $this->businessOwnerUser('P00903', 'BO00903', 'Test BO');
        $customer = $this->customer($bo, $partner, 'C00903');
        $order = $this->createOrder($bo, $partner, $customer, 'ORD-00903', OrderStatus::Packed);

        // Missing tracking number
        $response = $this->actingAs($user)->postJson(route('business.transactions.status', $order), [
            'status' => 'shipped',
            'tracking_number' => '',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertSame(OrderStatus::Packed, $order->fresh()->status);

        // With valid tracking number
        $response = $this->actingAs($user)->postJson(route('business.transactions.status', $order), [
            'status' => 'shipped',
            'tracking_number' => 'JNT-99887766MY',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'status' => 'shipped',
                    'tracking_number' => 'JNT-99887766MY',
                ],
            ]);

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Shipped, $fresh->status);
        $this->assertSame('JNT-99887766MY', $fresh->tracking_number);
        $this->assertNotNull($fresh->shipped_at);
    }

    public function test_business_owner_can_advance_shipped_order_to_completed(): void
    {
        [$user, $partner, $bo] = $this->businessOwnerUser('P00904', 'BO00904', 'Test BO');
        $customer = $this->customer($bo, $partner, 'C00904');
        $order = $this->createOrder($bo, $partner, $customer, 'ORD-00904', OrderStatus::Shipped, 'JNT-123456');

        $response = $this->actingAs($user)->postJson(route('business.transactions.status', $order), [
            'status' => 'completed',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'status' => 'completed',
                    'is_terminal' => true,
                ],
            ]);

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_illegal_status_jump_is_rejected(): void
    {
        [$user, $partner, $bo] = $this->businessOwnerUser('P00905', 'BO00905', 'Test BO');
        $customer = $this->customer($bo, $partner, 'C00905');
        $order = $this->createOrder($bo, $partner, $customer, 'ORD-00905', OrderStatus::Submitted);

        // Attempting to jump directly from Submitted to Completed
        $response = $this->actingAs($user)->postJson(route('business.transactions.status', $order), [
            'status' => 'completed',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertSame(OrderStatus::Submitted, $order->fresh()->status);
    }

    public function test_unauthorized_user_cannot_update_other_business_owner_order(): void
    {
        [$ownerUser, $partner, $bo] = $this->businessOwnerUser('P00906', 'BO00906', 'Owner BO');
        [$otherUser] = $this->businessOwnerUser('P00907', 'BO00907', 'Other BO');

        $customer = $this->customer($bo, $partner, 'C00906');
        $order = $this->createOrder($bo, $partner, $customer, 'ORD-00906', OrderStatus::Submitted);

        $response = $this->actingAs($otherUser)->postJson(route('business.transactions.status', $order), [
            'status' => 'paid',
        ]);

        $response->assertForbidden();
        $this->assertSame(OrderStatus::Submitted, $order->fresh()->status);
    }

    public function test_business_owner_can_advance_paid_order_directly_to_shipped_with_tracking(): void
    {
        [$user, $partner, $bo] = $this->businessOwnerUser('P00908', 'BO00908', 'Test BO');
        $customer = $this->customer($bo, $partner, 'C00908');
        $order = $this->createOrder($bo, $partner, $customer, 'ORD-00908', OrderStatus::Paid);

        $response = $this->actingAs($user)->postJson(route('business.transactions.status', $order), [
            'status' => 'shipped',
            'delivery_provider' => 'J&T Express',
            'tracking_number' => 'JNT-9988771122',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'status' => 'shipped',
                    'tracking_number' => 'J&T Express - JNT-9988771122',
                ],
            ]);

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Shipped, $fresh->status);
        $this->assertSame('J&T Express - JNT-9988771122', $fresh->tracking_number);
    }

    public function test_advancing_to_paid_records_payment_reference(): void
    {
        [$user, $partner, $bo] = $this->businessOwnerUser('P00909', 'BO00909', 'Test BO');
        $customer = $this->customer($bo, $partner, 'C00909');
        $order = $this->createOrder($bo, $partner, $customer, 'ORD-00909', OrderStatus::Submitted);

        $response = $this->actingAs($user)->postJson(route('business.transactions.status', $order), [
            'status' => 'paid',
            'reference_no' => 'DUITNOW-8837192',
            'payment_type' => 'bank_transfer',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'status' => 'paid',
                ],
            ]);

        $this->assertDatabaseHas(Payment::class, [
            'order_id' => $order->id,
            'reference_no' => 'DUITNOW-8837192',
            'status' => PaymentStatus::Verified->value,
        ]);
    }

    private function createOrder(
        BusinessOwner $bo,
        Partner $partner,
        Customer $customer,
        string $orderNo,
        OrderStatus $status,
        ?string $trackingNumber = null
    ): Order {
        return Order::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $partner->id,
            'customer_id' => $customer->id,
            'order_no' => $orderNo,
            'status' => $status,
            'tracking_number' => $trackingNumber,
            'subtotal' => 120.00,
            'total' => 120.00,
            'ordered_at' => now(),
            'paid_at' => in_array($status, [OrderStatus::Paid, OrderStatus::Packed, OrderStatus::Shipped, OrderStatus::Completed]) ? now() : null,
            'shipped_at' => in_array($status, [OrderStatus::Shipped, OrderStatus::Completed]) ? now() : null,
        ]);
    }

    private function customer(BusinessOwner $businessOwner, Partner $partner, string $customerNo): Customer
    {
        return Customer::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_no' => $customerNo,
            'category' => CustomerCategory::Customer,
            'is_vip' => false,
            'name' => "{$customerNo} Customer",
            'hp_no' => '60123456789',
        ]);
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

        return [$user, $partner, $businessOwner];
    }
}
