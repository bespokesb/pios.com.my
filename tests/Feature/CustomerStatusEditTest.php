<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerStatusEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessOwner $bo;

    protected Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);

        $this->partner = Partner::factory()->create();

        $this->bo = BusinessOwner::factory()->create([
            'user_id' => $this->user->id,
            'partner_id' => $this->partner->id,
            'business_owner_no' => 'BO999901',
            'applicant_name' => 'Owner Testing',
        ]);
    }

    public function test_bo_can_update_customer_status_to_all_six_categories(): void
    {
        $customer = Customer::factory()->create([
            'business_owner_id' => $this->bo->id,
            'partner_id' => $this->partner->id,
            'category' => CustomerCategory::Lead,
        ]);

        $statuses = [
            'lead' => CustomerCategory::Lead,
            'prospek' => CustomerCategory::Prospect,
            'customer' => CustomerCategory::Customer,
            'loyal_customer' => CustomerCategory::LoyalCustomer,
            'member' => CustomerCategory::Member,
            'partner' => CustomerCategory::Partner,
        ];

        foreach ($statuses as $inputStatus => $expectedEnum) {
            $response = $this->actingAs($this->user)->put(
                route('business.customers.update', $customer),
                [
                    'name' => $customer->name,
                    'hp_no' => '0123456789',
                    'category' => $inputStatus,
                ]
            );

            $response->assertRedirect(route('business.customers.index'));
            $this->assertDatabaseHas('customers', [
                'id' => $customer->id,
                'category' => $expectedEnum->value,
            ]);
        }
    }

    public function test_bo_can_update_customer_full_details(): void
    {
        $customer = Customer::factory()->create([
            'business_owner_id' => $this->bo->id,
            'partner_id' => $this->partner->id,
            'category' => CustomerCategory::Lead,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('business.customers.update', $customer),
            [
                'name' => 'Ahmad Razak Updated',
                'hp_no' => '0198765432',
                'email' => 'ahmad.updated@example.com',
                'category' => 'loyal_customer',
                'abo_no' => 'ABO-778899',
                'address' => 'No 45, Jalan Kemboja 2',
                'address_line_2' => 'Taman Flora Impian',
                'address_line_3' => 'Presint 4',
                'bandar' => 'Kajang',
                'negeri' => 'Selangor',
                'poskod' => '43000',
                'remarks' => 'Pelanggan setia sejak 2023',
            ]
        );

        $response->assertRedirect(route('business.customers.index'));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Ahmad Razak Updated',
            'email' => 'ahmad.updated@example.com',
            'category' => CustomerCategory::LoyalCustomer->value,
            'abo_no' => 'ABO-778899',
            'address' => 'No 45, Jalan Kemboja 2',
            'address_line_2' => 'Taman Flora Impian',
            'address_line_3' => 'Presint 4',
            'bandar' => 'Kajang',
            'negeri' => 'Selangor',
            'poskod' => '43000',
            'remarks' => 'Pelanggan setia sejak 2023',
        ]);
    }

    public function test_bo_cannot_update_customer_belonging_to_another_bo(): void
    {
        $otherUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Active,
        ]);
        $otherBo = BusinessOwner::factory()->create([
            'user_id' => $otherUser->id,
            'partner_id' => $this->partner->id,
        ]);

        $foreignCustomer = Customer::factory()->create([
            'business_owner_id' => $otherBo->id,
            'partner_id' => $this->partner->id,
            'name' => 'Other BO Customer',
        ]);

        $response = $this->actingAs($this->user)->put(
            route('business.customers.update', $foreignCustomer),
            [
                'name' => 'Attempted Hack Name',
                'hp_no' => '0129998877',
                'category' => 'customer',
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('customers', [
            'id' => $foreignCustomer->id,
            'name' => 'Other BO Customer',
        ]);
    }

    public function test_customer_page_renders_edit_button_and_status_dropdown(): void
    {
        $customer = Customer::factory()->create([
            'business_owner_id' => $this->bo->id,
            'partner_id' => $this->partner->id,
            'name' => 'Siti Nurhaliza',
            'category' => CustomerCategory::Customer,
        ]);

        $response = $this->actingAs($this->user)->get(route('business.customers.index'));

        $response->assertOk();
        $response->assertSee('Edit');
        $response->assertSee('editCustomerModal');
        $response->assertSee('Siti Nurhaliza');
        $response->assertSee('Loyal Customer');
        $response->assertSee('Partner');
    }

    public function test_products_page_renders_and_filters_four_tabs(): void
    {
        $typeProduct = ProductType::firstOrCreate(['code' => 'products'], ['name' => 'Products']);
        $typeService = ProductType::firstOrCreate(['code' => 'services'], ['name' => 'Services']);
        $typeProgramme = ProductType::firstOrCreate(['code' => 'programme'], ['name' => 'Programme']);

        $productItem = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Special Health Honey',
            'product_type_id' => $typeProduct->id,
            'status' => ProductStatus::Active,
        ]);

        $serviceItem = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Consultation Service Package',
            'product_type_id' => $typeService->id,
            'status' => ProductStatus::Active,
        ]);

        $programmeItem = Product::factory()->create([
            'business_owner_id' => $this->bo->id,
            'name' => 'Weight Loss Coaching Programme',
            'product_type_id' => $typeProgramme->id,
            'status' => ProductStatus::Active,
        ]);

        // Tab "All"
        $respAll = $this->actingAs($this->user)->get(route('business.products.index', ['category' => 'all']));
        $respAll->assertOk();
        $respAll->assertSee('Special Health Honey');
        $respAll->assertSee('Consultation Service Package');
        $respAll->assertSee('Weight Loss Coaching Programme');
        $respAll->assertSeeText('All');
        $respAll->assertSeeText('Products');
        $respAll->assertSeeText('Services');
        $respAll->assertSeeText('Programme');

        // Tab "Products"
        $respProd = $this->actingAs($this->user)->get(route('business.products.index', ['category' => 'products']));
        $respProd->assertOk();
        $respProd->assertSee('Special Health Honey');
        $respProd->assertDontSee('Consultation Service Package');
        $respProd->assertDontSee('Weight Loss Coaching Programme');

        // Tab "Services"
        $respServ = $this->actingAs($this->user)->get(route('business.products.index', ['category' => 'services']));
        $respServ->assertOk();
        $respServ->assertSee('Consultation Service Package');
        $respServ->assertDontSee('Special Health Honey');
        $respServ->assertDontSee('Weight Loss Coaching Programme');

        // Tab "Programme"
        $respProg = $this->actingAs($this->user)->get(route('business.products.index', ['category' => 'programme']));
        $respProg->assertOk();
        $respProg->assertSee('Weight Loss Coaching Programme');
        $respProg->assertDontSee('Special Health Honey');
        $respProg->assertDontSee('Consultation Service Package');
    }
}
