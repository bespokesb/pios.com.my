<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Widgets\OrderStatsOverview;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderDashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_widget_is_hidden_from_non_business_owners(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $this->actingAs($admin);

        $this->assertFalse(OrderStatsOverview::canView());
    }

    public function test_revenue_counts_only_own_paid_onward_orders(): void
    {
        [$user, $bo] = $this->businessOwner('P100001', 'BO100001');
        [, $otherBo] = $this->businessOwner('P100002', 'BO100002');

        $this->order($bo, OrderStatus::Submitted, 'O1', 100);   // unpaid → not revenue
        $this->order($bo, OrderStatus::Paid, 'O2', 60);         // revenue
        $this->order($bo, OrderStatus::Completed, 'O3', 40);    // revenue
        $this->order($bo, OrderStatus::Cancelled, 'O4', 500);   // excluded
        $this->order($otherBo, OrderStatus::Paid, 'O5', 999);   // foreign → excluded

        $this->actingAs($user);
        Filament::setCurrentPanel('business');

        // Own confirmed revenue = 60 + 40 = 100; awaiting = 1; total own = 4.
        Livewire::test(OrderStatsOverview::class)
            ->assertSee('RM 100.00')
            ->assertDontSee('RM 999.00');
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

    private function order(BusinessOwner $bo, OrderStatus $status, string $no, float $total): Order
    {
        $customer = Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_no' => 'C-'.$bo->id.'-'.$no,
            'name' => 'Cust',
            'hp_no' => '60120000000',
        ]);

        return Order::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $bo->partner_id,
            'customer_id' => $customer->id,
            'order_no' => $bo->business_owner_no.'-'.$no,
            'status' => $status,
            'subtotal' => $total,
            'total' => $total,
            'ordered_at' => now(),
        ]);
    }
}
