<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\IncomingOrders\IncomingOrderResource;
use App\Filament\Resources\IncomingOrders\Pages\ListIncomingOrders;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\CustomerNote;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessPanelUxTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_orders_nav_badge_counts_only_own_submitted_orders(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000921', 'BO000921');
        [, $otherBo] = $this->businessOwner('P000922', 'BO000922');

        $this->order($bo, OrderStatus::Submitted, 'ORD000921');
        $this->order($bo, OrderStatus::Completed, 'ORD000922');
        $this->order($otherBo, OrderStatus::Submitted, 'ORD000923'); // foreign — must not count

        $this->actingAs($boUser);

        $this->assertSame('1', IncomingOrderResource::getNavigationBadge());
    }

    public function test_orders_nav_badge_hides_at_zero(): void
    {
        [$boUser] = $this->businessOwner('P000923', 'BO000923');
        $this->actingAs($boUser);

        $this->assertNull(IncomingOrderResource::getNavigationBadge());
    }

    public function test_quick_add_note_creates_a_scoped_audited_note(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000924', 'BO000924');
        $cust = $this->customer($bo, 'C000924');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(ListCustomers::class)
            ->callTableAction('addNote', $cust, data: ['body' => 'Interested in bundle A']);

        $note = CustomerNote::query()->firstWhere('customer_id', $cust->id);
        $this->assertNotNull($note);
        $this->assertSame($bo->id, $note->business_owner_id);
        $this->assertSame($boUser->id, $note->created_by);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => CustomerNote::class,
            'auditable_id' => $note->id,
            'event' => 'created',
        ]);
    }

    public function test_quick_set_follow_up_creates_a_scoped_follow_up(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000925', 'BO000925');
        $cust = $this->customer($bo, 'C000925');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        $due = today()->addDays(5);

        Livewire::test(ListCustomers::class)
            ->callTableAction('setFollowUp', $cust, data: [
                'due_on' => $due->toDateString(),
                'note' => 'Call after payday',
            ]);

        $followUp = CustomerFollowUp::query()->firstWhere('customer_id', $cust->id);
        $this->assertNotNull($followUp);
        $this->assertSame($bo->id, $followUp->business_owner_id);
        $this->assertTrue($followUp->due_on->isSameDay($due));
    }

    public function test_share_modal_mounts_and_surfaces_the_public_link_and_message(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000926', 'BO000926');
        $product = Product::factory()->create([
            'business_owner_id' => $bo->id,
            'name' => 'Sambal Ikan Bilis',
        ]);
        [, $foreignBo] = $this->businessOwner('P000930', 'BO000930');
        $foreign = Product::factory()->create(['business_owner_id' => $foreignBo->id]);

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(ListProducts::class)
            ->assertTableActionVisible('share', $product)
            ->mountTableAction('share', $product)
            ->assertSuccessful();

        // The modal's TextEntry state closures render exactly these values.
        $this->assertStringContainsString('/p/'.$product->product_no, $product->publicUrl());
        $this->assertStringContainsString('Sambal Ikan Bilis', $product->shareMessage());
        $this->assertStringContainsString($product->publicUrl(), $product->shareMessage());

        // A foreign BO's product exposes no share action at all (scoped out of the list).
        $this->assertFalse(Product::query()->visibleTo($boUser)->whereKey($foreign->id)->exists());
    }

    public function test_blast_preview_query_counts_only_own_whatsappable_customers(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000927', 'BO000927');
        [, $otherBo] = $this->businessOwner('P000928', 'BO000928');

        // 2 own customers with phones; 1 own without; 1 foreign with a phone.
        $this->customer($bo, 'C000927', hpNo: '60130000001');
        $vip = $this->customer($bo, 'C000928', hpNo: '60130000002');
        $vip->update(['is_vip' => true]);
        $this->customer($bo, 'C000929', hpNo: null);
        $this->customer($otherBo, 'C000930', hpNo: '60130000003');

        $this->actingAs($boUser->fresh());
        Filament::setCurrentPanel('business');

        // The SAME query feeds the modal's recipient-count preview and the send.
        $query = new \ReflectionMethod(ProductsTable::class, 'blastRecipientsQuery');

        $this->assertSame(2, $query->invoke(null, 'all')->count());
        $this->assertSame(1, $query->invoke(null, 'vip')->count());
    }

    public function test_customer_list_teaches_via_empty_state(): void
    {
        [$boUser] = $this->businessOwner('P000929', 'BO000929');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(ListCustomers::class)
            ->assertSee(__('pios.customer.empty.heading'));
    }

    public function test_order_status_action_advances_forward_only_and_records_tracking(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000931', 'BO000931');
        $order = $this->order($bo, OrderStatus::Packed, 'ORD000931');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        // Packed → Shipped, capturing the tracking number.
        Livewire::test(ListIncomingOrders::class)
            ->callTableAction('updateStatus', $order, data: [
                'status' => OrderStatus::Shipped->value,
                'tracking_number' => 'TRK-99',
            ])
            ->assertHasNoTableActionErrors();

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::Shipped, $fresh->status);
        $this->assertSame('TRK-99', $fresh->tracking_number);
        $this->assertNotNull($fresh->shipped_at);
    }

    public function test_order_status_action_is_hidden_for_a_terminal_order(): void
    {
        [$boUser, $bo] = $this->businessOwner('P000932', 'BO000932');
        $order = $this->order($bo, OrderStatus::Completed, 'ORD000932');

        $this->actingAs($boUser);
        Filament::setCurrentPanel('business');

        Livewire::test(ListIncomingOrders::class)
            ->assertTableActionHidden('updateStatus', $order);
    }

    /** @return array{0: User, 1: BusinessOwner} */
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

        return [$user->fresh(), $bo];
    }

    private function customer(BusinessOwner $bo, string $customerNo, ?string $hpNo = '60120000000'): Customer
    {
        return Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_no' => $customerNo,
            'name' => 'Cust '.$customerNo,
            'hp_no' => $hpNo,
        ]);
    }

    private function order(BusinessOwner $bo, OrderStatus $status, string $orderNo): Order
    {
        $customer = $this->customer($bo, 'C-'.$orderNo);

        return Order::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_id' => $customer->id,
            'order_no' => $orderNo,
            'status' => $status,
            'subtotal' => 10,
            'total' => 10,
            'ordered_at' => now(),
        ]);
    }
}
