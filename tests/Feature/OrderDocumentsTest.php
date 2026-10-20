<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_invoice_for_a_submitted_order(): void
    {
        [$user, $order] = $this->orderFor(OrderStatus::Submitted);

        $this->actingAs($user)
            ->get(route('order.invoice', $order))
            ->assertOk()
            ->assertSee($order->order_no)
            ->assertSee('Invoice');
    }

    public function test_invoice_is_not_available_while_draft(): void
    {
        [$user, $order] = $this->orderFor(OrderStatus::Draft);

        $this->actingAs($user)->get(route('order.invoice', $order))->assertNotFound();
    }

    public function test_receipt_only_available_once_paid(): void
    {
        [$user, $order] = $this->orderFor(OrderStatus::Submitted);

        // Not paid yet → 404.
        $this->actingAs($user)->get(route('order.receipt', $order))->assertNotFound();

        $order->transitionTo(OrderStatus::Paid);

        $this->actingAs($user)
            ->get(route('order.receipt', $order))
            ->assertOk()
            ->assertSee('Receipt')
            ->assertSee('PAID');
    }

    public function test_a_foreign_business_owner_cannot_open_another_bos_document(): void
    {
        [, $order] = $this->orderFor(OrderStatus::Submitted);
        [$otherUser] = $this->orderFor(OrderStatus::Submitted, 'B');

        $this->actingAs($otherUser)->get(route('order.invoice', $order))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        [, $order] = $this->orderFor(OrderStatus::Submitted);

        $this->get(route('order.invoice', $order))->assertRedirect();
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function orderFor(OrderStatus $status, string $suffix = 'A'): array
    {
        $user = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->forPartner($partner)->create(['user_id' => $user->id]);

        $customer = Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_no' => 'C-DOC-'.$suffix.$status->value,
            'name' => 'Doc Customer',
            'hp_no' => '60120000000',
        ]);

        $order = Order::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_id' => $customer->id,
            'order_no' => 'ORD-DOC-'.$suffix.strtoupper($status->value),
            'status' => $status,
            'subtotal' => 50,
            'total' => 50,
            'ordered_at' => now(),
        ]);

        $product = Product::factory()->forBusinessOwner($bo)->create(['name' => 'Madu Kelulut']);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Madu Kelulut',
            'quantity' => 2,
            'unit_price' => 25,
            'line_total' => 50,
        ]);

        return [$user->fresh(), $order];
    }
}
