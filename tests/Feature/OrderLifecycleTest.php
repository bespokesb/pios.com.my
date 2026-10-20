<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_status_map_is_forward_only_with_cancel_and_terminal_ends(): void
    {
        $this->assertSame([OrderStatus::Submitted, OrderStatus::Cancelled], OrderStatus::Draft->nextStatuses());
        $this->assertSame([OrderStatus::Paid, OrderStatus::Cancelled], OrderStatus::Submitted->nextStatuses());
        $this->assertSame([OrderStatus::Packed, OrderStatus::Cancelled], OrderStatus::Paid->nextStatuses());
        $this->assertSame([OrderStatus::Shipped, OrderStatus::Cancelled], OrderStatus::Packed->nextStatuses());
        $this->assertSame([OrderStatus::Completed, OrderStatus::Cancelled], OrderStatus::Shipped->nextStatuses());

        $this->assertTrue(OrderStatus::Completed->isTerminal());
        $this->assertTrue(OrderStatus::Cancelled->isTerminal());
        $this->assertFalse(OrderStatus::Submitted->isTerminal());

        // No skipping ahead.
        $this->assertFalse(OrderStatus::Submitted->canTransitionTo(OrderStatus::Shipped));
        $this->assertFalse(OrderStatus::Submitted->canTransitionTo(OrderStatus::Completed));
        $this->assertTrue(OrderStatus::Submitted->canTransitionTo(OrderStatus::Paid));
    }

    public function test_transition_advances_and_stamps_timestamps(): void
    {
        $order = $this->order(OrderStatus::Submitted);

        $order->transitionTo(OrderStatus::Paid);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);

        $order->transitionTo(OrderStatus::Packed);
        $order->transitionTo(OrderStatus::Shipped, 'TRK-12345');

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Shipped, $fresh->status);
        $this->assertSame('TRK-12345', $fresh->tracking_number);
        $this->assertNotNull($fresh->shipped_at);
    }

    public function test_an_illegal_jump_throws_and_leaves_the_order_unchanged(): void
    {
        $order = $this->order(OrderStatus::Submitted);

        try {
            $order->transitionTo(OrderStatus::Shipped);
            $this->fail('Expected an illegal transition to throw.');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertSame(OrderStatus::Submitted, $order->fresh()->status);
    }

    public function test_submitting_a_draft_order_stamps_ordered_at_so_it_enters_revenue_windows(): void
    {
        $order = $this->order(OrderStatus::Draft);
        $order->forceFill(['ordered_at' => null])->save();

        $order->transitionTo(OrderStatus::Submitted);

        // ordered_at is stamped, so the order is no longer invisible to the
        // whereBetween('ordered_at', …) revenue windows (dashboard/report/chart).
        $this->assertNotNull($order->fresh()->ordered_at);
    }

    public function test_transition_never_overwrites_an_existing_ordered_at(): void
    {
        $order = $this->order(OrderStatus::Draft);
        $original = now()->subDays(3)->startOfSecond();
        $order->forceFill(['ordered_at' => $original])->save();

        $order->transitionTo(OrderStatus::Submitted);

        $this->assertTrue($order->fresh()->ordered_at->equalTo($original));
    }

    public function test_shipping_without_a_tracking_number_is_rejected(): void
    {
        $order = $this->order(OrderStatus::Packed);

        try {
            $order->transitionTo(OrderStatus::Shipped);
            $this->fail('Expected shipping without a tracking number to throw.');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertSame(OrderStatus::Packed, $order->fresh()->status);
        $this->assertNull($order->fresh()->shipped_at);
    }

    public function test_an_order_can_be_cancelled_from_a_non_terminal_status(): void
    {
        $order = $this->order(OrderStatus::Paid);

        $order->transitionTo(OrderStatus::Cancelled);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    private function order(OrderStatus $status): Order
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->forPartner($partner)->create();
        $customer = Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_no' => 'C-'.$status->value,
            'name' => 'Lifecycle Customer',
            'hp_no' => '60120000000',
        ]);

        return Order::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_id' => $customer->id,
            'order_no' => 'ORD-LC-'.strtoupper($status->value),
            'status' => $status,
            'subtotal' => 10,
            'total' => 10,
            'ordered_at' => now(),
        ]);
    }
}
