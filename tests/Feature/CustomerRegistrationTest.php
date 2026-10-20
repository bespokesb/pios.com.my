<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_create_customer_under_own_profile(): void
    {
        [$businessOwnerUser, $partner, $businessOwner] = $this->businessOwnerUser('P000601', 'BO000601', 'Business Owner One');
        [, , $otherBusinessOwner] = $this->businessOwnerUser('P000602', 'BO000602', 'Business Owner Two');

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'business_owner_id' => $otherBusinessOwner->id,
                'name' => 'Customer One',
                'nick_name' => 'Cust',
                'hp_no' => '60130010001',
                'kp_no' => '900101010001',
                'email' => 'customer-one@pios.test',
                'category' => CustomerCategory::Customer->value,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->firstWhere('email', 'customer-one@pios.test');

        $this->assertNotNull($customer);
        $this->assertSame($businessOwner->id, $customer->business_owner_id);
        $this->assertSame($partner->id, $customer->partner_id);
        $this->assertSame('C000001', $customer->customer_no);
        $this->assertFalse($customer->is_vip);
        $this->assertSame($businessOwnerUser->id, $customer->created_by);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
            'event' => 'created',
        ]);
    }

    public function test_business_owner_creates_customer_with_name_phone_email_only(): void
    {
        [$businessOwnerUser, $partner, $businessOwner] = $this->businessOwnerUser('P000611', 'BO000611', 'Minimal BO');

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'Walk In Customer',
                'hp_no' => '60130020001',
                'email' => 'walkin@pios.test',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->firstWhere('email', 'walkin@pios.test');

        $this->assertNotNull($customer);
        $this->assertSame('Walk In Customer', $customer->name);
        $this->assertSame('60130020001', $customer->hp_no);
        // Owner + partner resolved server-side from the business owner's own profile.
        $this->assertSame($businessOwner->id, $customer->business_owner_id);
        $this->assertSame($partner->id, $customer->partner_id);
        // Category falls back to Lead; no IC, address, or VIP number captured.
        $this->assertSame(CustomerCategory::Lead, $customer->category);
        $this->assertNull($customer->kp_no);
        $this->assertNull($customer->address);
        $this->assertFalse($customer->is_vip);
    }

    public function test_business_owner_can_capture_optional_profile_fields(): void
    {
        [$businessOwnerUser] = $this->businessOwnerUser('P000618', 'BO000618', 'Profile BO');

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'Detailed Customer',
                'hp_no' => '60130020618',
                'age' => 34,
                'marital_status' => 'married',
                'occupation' => 'Teacher',
                'remarks' => 'Prefers WhatsApp after 6pm.',
                // Standard address form (shared with the Business Owner page).
                'address' => '12 Jalan Contoh',
                'address_line_2' => 'Taman Sejahtera',
                'address_line_3' => 'Seksyen 7',
                'bandar' => 'Shah Alam',
                'negeri' => 'Selangor',
                'poskod' => '40000',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->firstWhere('name', 'Detailed Customer');

        $this->assertNotNull($customer);
        $this->assertSame(34, $customer->age);
        $this->assertSame('married', $customer->marital_status);
        $this->assertSame('Teacher', $customer->occupation);
        $this->assertSame('Prefers WhatsApp after 6pm.', $customer->remarks);
        $this->assertSame('12 Jalan Contoh', $customer->address);
        $this->assertSame('Taman Sejahtera', $customer->address_line_2);
        $this->assertSame('Seksyen 7', $customer->address_line_3);
        $this->assertSame('Shah Alam', $customer->bandar);
        $this->assertSame('Selangor', $customer->negeri);
        $this->assertSame('40000', $customer->poskod);
    }

    public function test_phone_typed_as_local_part_is_stored_with_the_60_country_code(): void
    {
        [$businessOwnerUser] = $this->businessOwnerUser('P000620', 'BO000620', 'Prefix BO');

        $this->actingAs($businessOwnerUser);

        // The +60 prefix is shown on the field; the user types only the local part.
        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'Local Part Customer',
                'hp_no' => '12-345 6789',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->firstWhere('name', 'Local Part Customer');

        $this->assertNotNull($customer);
        // Stored normalised to full international digits, regardless of how it was typed.
        $this->assertSame('60123456789', $customer->hp_no);
    }

    public function test_business_owner_customer_rejects_an_invalid_phone(): void
    {
        [$businessOwnerUser] = $this->businessOwnerUser('P000619', 'BO000619', 'Bad Phone BO');

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'Bad Phone Customer',
                'hp_no' => '99',
            ])
            ->call('create')
            ->assertHasFormErrors(['hp_no']);
    }

    public function test_business_owner_customer_requires_phone(): void
    {
        [$businessOwnerUser] = $this->businessOwnerUser('P000612', 'BO000612', 'Phone Required BO');

        $this->actingAs($businessOwnerUser);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'No Phone Customer',
            ])
            ->call('create')
            ->assertHasFormErrors(['hp_no']);
    }

    public function test_admin_can_create_vip_customer_for_selected_business_owner(): void
    {
        $admin = $this->adminUser();
        [$partner, $businessOwner] = $this->partnerAndBusinessOwner('P000603', 'BO000603', 'Admin Selected BO');

        $this->actingAs($admin);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'name' => 'VIP Customer One',
                'hp_no' => '60130010002',
                'kp_no' => '900101010002',
                'category' => CustomerCategory::Customer->value,
                'is_vip' => true,
                'abo_no' => 'ABO-10002',
                'dd_name' => 'Diamond Director',
                'dd_hp_no' => '60190000001',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->firstWhere('name', 'VIP Customer One');

        $this->assertNotNull($customer);
        $this->assertSame($businessOwner->id, $customer->business_owner_id);
        $this->assertSame($partner->id, $customer->partner_id);
        $this->assertSame('C000001', $customer->customer_no);
        $this->assertTrue($customer->is_vip);
        $this->assertSame(CustomerCategory::Customer, $customer->category);
    }

    public function test_vip_customer_requires_abo_and_non_vip_saves_plainly(): void
    {
        $admin = $this->adminUser();
        [, $businessOwner] = $this->partnerAndBusinessOwner('P000604', 'BO000604', 'Eligibility BO');

        $this->actingAs($admin);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'name' => 'VIP Missing ABO',
                'hp_no' => '60130010003',
                'kp_no' => '900101010003',
                'category' => CustomerCategory::Customer->value,
                'is_vip' => true,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['abo_no']);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'name' => 'Non VIP Customer One',
                'hp_no' => '60130010004',
                'kp_no' => '900101010004',
                'category' => CustomerCategory::Customer->value,
                'is_vip' => false,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->firstWhere('name', 'Non VIP Customer One');

        $this->assertNotNull($customer);
        $this->assertSame('C000001', $customer->customer_no);
        $this->assertFalse($customer->is_vip);
        $this->assertSame(CustomerCategory::Customer, $customer->category);
    }

    public function test_duplicate_customer_hp_and_kp_are_blocked_within_partner_scope(): void
    {
        $admin = $this->adminUser();
        [$partner, $businessOwner] = $this->partnerAndBusinessOwner('P000605', 'BO000605', 'Duplicate BO');
        [, $otherBusinessOwner] = $this->partnerAndBusinessOwner('P000606', 'BO000606', 'Other Partner BO');

        Customer::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_no' => 'C000900',
            'category' => CustomerCategory::Customer,
            'name' => 'Existing Customer',
            'hp_no' => '60130010005',
            'kp_no' => '900101010005',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'name' => 'Duplicate KP',
                'hp_no' => '60130010006',
                'kp_no' => '900101010005',
                'category' => CustomerCategory::Customer->value,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['kp_no']);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'business_owner_id' => $businessOwner->id,
                'name' => 'Duplicate HP',
                'hp_no' => '60130010005',
                'kp_no' => '900101010006',
                'category' => CustomerCategory::Customer->value,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['hp_no']);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'business_owner_id' => $otherBusinessOwner->id,
                'name' => 'Allowed Other Partner',
                'hp_no' => '60130010005',
                'kp_no' => '900101010005',
                'category' => CustomerCategory::Customer->value,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_partner_can_view_downstream_customers_and_create_own_but_not_edit_bo_customers(): void
    {
        [$partnerUser, $partner] = $this->partnerUser('P000607', 'Read Only Partner');
        $businessOwner = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000607',
            'applicant_name' => 'Read Only BO',
        ]);
        $customer = Customer::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_no' => 'C000607',
            'category' => CustomerCategory::Customer,
            'name' => 'Visible Customer',
        ]);
        [$otherPartnerUser] = $this->partnerUser('P000608', 'Other Read Only Partner');

        $this->assertTrue(Gate::forUser($partnerUser)->allows('viewAny', Customer::class));
        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $customer));
        // Partners may now own customers directly, so create is allowed...
        $this->assertTrue(Gate::forUser($partnerUser)->allows('create', Customer::class));
        // ...but a Business-Owner-owned customer stays read-only to them (VIP tagging aside).
        $this->assertFalse(Gate::forUser($partnerUser)->allows('update', $customer));
        $this->assertFalse(Gate::forUser($otherPartnerUser)->allows('view', $customer));
    }

    public function test_customer_admin_routes_are_registered(): void
    {
        $routes = collect(\Route::getRoutes())->map(fn ($route): string => $route->uri())->all();

        $this->assertContains('admin/customers', $routes);
        $this->assertContains('admin/customers/create', $routes);
        $this->assertContains('admin/customers/{record}/edit', $routes);
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
            'name' => "{$category->label()} Customer",
        ]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }
}
