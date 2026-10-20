<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\BusinessSalesReport;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessSalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_daily_report_sums_only_own_paid_orders_for_the_day(): void
    {
        [$user, $bo] = $this->businessOwner('P200001', 'BO200001');
        [, $otherBo] = $this->businessOwner('P200002', 'BO200002');

        $today = now('Asia/Kuala_Lumpur');

        $this->order($bo, OrderStatus::Paid, 'D1', 70, $today);
        $this->order($bo, OrderStatus::Submitted, 'D2', 30, $today);            // unpaid — excluded from revenue
        $this->order($bo, OrderStatus::Paid, 'D3', 500, $today->copy()->subDays(3)); // other day — excluded
        $this->order($otherBo, OrderStatus::Paid, 'D4', 999, $today);           // foreign — excluded

        $this->actingAs($user);
        Filament::setCurrentPanel('business');

        Livewire::test(BusinessSalesReport::class)
            ->set('period', 'daily')
            ->set('date', $today->toDateString())
            ->assertSee('RM 70.00')
            ->assertDontSee('RM 999.00');
    }

    public function test_weekly_report_shows_a_seven_day_breakdown(): void
    {
        [$user, $bo] = $this->businessOwner('P200003', 'BO200003');
        $anchor = now('Asia/Kuala_Lumpur')->startOfWeek()->addDay();

        $this->order($bo, OrderStatus::Completed, 'W1', 120, $anchor);

        $this->actingAs($user);
        Filament::setCurrentPanel('business');

        Livewire::test(BusinessSalesReport::class)
            ->set('period', 'weekly')
            ->set('date', $anchor->toDateString())
            ->assertSee('RM 120.00')
            ->assertSee(__('pios.report.sales.day'));
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

    private function order(BusinessOwner $bo, OrderStatus $status, string $no, float $total, Carbon $orderedAt): Order
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
            'ordered_at' => $orderedAt,
        ]);
    }
}
