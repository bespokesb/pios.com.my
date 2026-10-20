<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\OperationalReports;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Fee;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Services\OperationalReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_service_respects_admin_and_partner_hierarchy_scope(): void
    {
        $admin = $this->adminUser();
        [$partnerUser, $partnerOne] = $this->partnerUser('P001001', 'Report Partner One');
        $partnerTwo = Partner::query()->create([
            'partner_no' => 'P001002',
            'applicant_name' => 'Report Partner Two',
        ]);
        $businessOwnerOne = $this->businessOwner($partnerOne, 'BO001001', 'Report BO One');
        $businessOwnerTwo = $this->businessOwner($partnerTwo, 'BO001002', 'Report BO Two');
        $customerOne = $this->customer($businessOwnerOne, $partnerOne, 'C001001', CustomerCategory::Customer);
        $customerTwo = $this->customer($businessOwnerTwo, $partnerTwo, 'C001002', CustomerCategory::Customer, isVip: true);
        $this->feePaymentAndOrder($businessOwnerOne, $partnerOne, $customerOne, 'ORD001001', 50);
        $this->feePaymentAndOrder($businessOwnerTwo, $partnerTwo, $customerTwo, 'ORD001002', 75);

        $service = app(OperationalReportService::class);

        $adminReport = $service->report($admin);
        $partnerReport = $service->report($partnerUser);

        $this->assertSame(2, $adminReport['summary']['business_owner_count']);
        $this->assertSame(2, $adminReport['summary']['customer_count']);
        $this->assertSame(2, $adminReport['summary']['order_count']);
        $this->assertSame(1, $partnerReport['summary']['business_owner_count']);
        $this->assertSame(1, $partnerReport['summary']['customer_count']);
        $this->assertSame(1, $partnerReport['summary']['order_count']);
        $this->assertSame('ORD001001', $partnerReport['orders']->first()->order_no);
    }

    public function test_report_service_applies_category_product_type_status_and_date_filters(): void
    {
        $admin = $this->adminUser();
        $partner = Partner::query()->create([
            'partner_no' => 'P001003',
            'applicant_name' => 'Filter Partner',
        ]);
        $businessOwner = $this->businessOwner($partner, 'BO001003', 'Filter BO');
        $regularCustomer = $this->customer($businessOwner, $partner, 'C001003', CustomerCategory::Prospect);
        $vipCustomer = $this->customer($businessOwner, $partner, 'C001004', CustomerCategory::Customer, isVip: true);
        $productType = ProductType::query()->create([
            'name' => 'Health',
            'code' => 'health',
        ]);
        $categoryProduct = Product::query()->create([
            'product_type_id' => $productType->id,
            'product_no' => 'PRD001003',
            'name' => 'Filtered Category Product',
            'selling_price' => 40,
            'status' => 'active',
        ]);
        $otherProduct = Product::query()->create([
            'product_no' => 'PRD001004',
            'name' => 'Filtered Other Product',
            'selling_price' => 30,
            'status' => 'active',
        ]);
        $submittedOrder = $this->order($businessOwner, $partner, $vipCustomer, 'ORD001003', OrderStatus::Submitted, $categoryProduct, 40);
        $draftOrder = $this->order($businessOwner, $partner, $regularCustomer, 'ORD001004', OrderStatus::Draft, $otherProduct, 30);
        $draftOrder->forceFill(['created_at' => now()->subDays(10)])->save();

        $service = app(OperationalReportService::class);

        $categoryReport = $service->report($admin, ['customer_category' => CustomerCategory::Customer->value]);
        $productTypeReport = $service->report($admin, ['product_type_id' => $productType->id]);
        $statusReport = $service->report($admin, ['status' => OrderStatus::Submitted->value]);
        $dateReport = $service->report($admin, ['date_from' => now()->subDay()->toDateString()]);

        $this->assertSame(1, $categoryReport['summary']['customer_count']);
        $this->assertSame('C001004', $categoryReport['customers']->first()->customer_no);
        $this->assertSame(1, $productTypeReport['summary']['order_count']);
        $this->assertSame($submittedOrder->order_no, $productTypeReport['orders']->first()->order_no);
        $this->assertSame(1, $statusReport['summary']['order_count']);
        $this->assertSame($submittedOrder->order_no, $statusReport['orders']->first()->order_no);
        $this->assertSame(1, $dateReport['summary']['order_count']);
        $this->assertSame($submittedOrder->order_no, $dateReport['orders']->first()->order_no);
    }

    public function test_operational_reports_page_route_and_access_are_registered(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/operational-reports', $routes);
        $this->assertTrue(OperationalReports::canAccess());

        $this->get('/admin/operational-reports')
            ->assertOk()
            ->assertSee('PIOS hierarchy performance dashboard')
            ->assertSee('pios-hero', escape: false);
    }

    public function test_filament_login_uses_branded_split_layout(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('pios-login-shell', escape: false)
            ->assertSee('brand/pios-wordmark.png', escape: false)
            ->assertSee('brand/pak-itam-logo.png', escape: false);
    }

    public function test_operational_reports_page_can_clear_active_filters(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        Livewire::test(OperationalReports::class)
            ->set('date_from', now()->subMonth()->toDateString())
            ->set('customer_category', CustomerCategory::Customer->value)
            ->assertSet('activeFilterCount', 2)
            ->call('clearFilters')
            ->assertSet('date_from', null)
            ->assertSet('customer_category', null)
            ->assertSet('activeFilterCount', 0);
    }

    private function feePaymentAndOrder(
        BusinessOwner $businessOwner,
        Partner $partner,
        Customer $customer,
        string $orderNo,
        int $amount
    ): void {
        $fee = Fee::query()->create([
            'partner_id' => $partner->id,
            'business_owner_id' => $businessOwner->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'amount' => $amount,
            'status' => FeeStatus::Unpaid,
        ]);
        Payment::query()->create([
            'fee_id' => $fee->id,
            'payment_type' => 'manual',
            'amount' => $amount,
            'status' => PaymentStatus::Verified,
        ]);

        $product = Product::query()->create([
            'product_no' => "PRD{$orderNo}",
            'name' => "Product {$orderNo}",
            'selling_price' => $amount,
            'status' => 'active',
        ]);

        $this->order($businessOwner, $partner, $customer, $orderNo, OrderStatus::Submitted, $product, $amount);
    }

    private function order(
        BusinessOwner $businessOwner,
        Partner $partner,
        Customer $customer,
        string $orderNo,
        OrderStatus $status,
        Product $product,
        int $amount
    ): Order {
        $order = Order::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_id' => $customer->id,
            'order_no' => $orderNo,
            'status' => $status,
            'subtotal' => $amount,
            'total' => $amount,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => $amount,
            'line_total' => $amount,
        ]);

        return $order;
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

    private function businessOwner(Partner $partner, string $businessOwnerNo, string $name): BusinessOwner
    {
        return BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => $name,
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

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
