<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\CustomerTag;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_managed_tag_options_are_active_sorted_and_merge_existing_values(): void
    {
        CustomerTag::query()->create(['name' => 'vip']);
        CustomerTag::query()->create(['name' => 'ramadan']);
        CustomerTag::query()->create(['name' => 'archived-tag', 'status' => RecordStatus::Inactive]);

        // Active managed tags, sorted; inactive excluded; a legacy value merged in.
        $this->assertSame(
            ['legacy', 'ramadan', 'vip'],
            array_values(CustomerTag::options(['legacy'])),
        );
        $this->assertArrayNotHasKey('archived-tag', CustomerTag::options());
    }

    public function test_customer_tags_resource_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SysAdmin, 'status' => UserStatus::Active]);
        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', CustomerTag::class));
        $this->assertTrue(Gate::forUser($boUser)->denies('viewAny', CustomerTag::class));
    }

    public function test_customer_tags_cast_to_array(): void
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $partner->id]);
        $customer = Customer::factory()->forBusinessOwner($bo)->create(['tags' => ['vvip', 'ramadan']]);

        $this->assertSame(['vvip', 'ramadan'], $customer->fresh()->tags);
    }

    public function test_business_owner_can_tag_own_customer_and_partner_can_tag_downstream(): void
    {
        $partner = Partner::factory()->create();
        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create(['user_id' => $boUser->id, 'partner_id' => $partner->id]);
        $customer = Customer::factory()->forBusinessOwner($bo)->create();

        // Both the owning BO and the managing partner may tag.
        $this->assertTrue(Gate::forUser($boUser->fresh())->allows('tagLabels', $customer));
        $this->assertTrue(Gate::forUser($partner->user)->allows('tagLabels', $customer));

        // A stranger partner may not.
        $stranger = Partner::factory()->create();
        $this->assertFalse(Gate::forUser($stranger->user)->allows('tagLabels', $customer));
    }

    public function test_business_owner_applies_tags_via_action(): void
    {
        $partner = Partner::factory()->create();
        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        $bo = BusinessOwner::factory()->create(['user_id' => $boUser->id, 'partner_id' => $partner->id]);
        $customer = Customer::factory()->forBusinessOwner($bo)->create();

        // DP-15: tags are picked from the admin-managed list.
        CustomerTag::query()->create(['name' => 'loyal']);
        CustomerTag::query()->create(['name' => 'follow-up']);

        $this->actingAs($boUser->fresh());

        Livewire::test(ListCustomers::class)
            ->callTableAction('manageTags', $customer, data: ['tags' => ['loyal', 'follow-up']]);

        // Tags picked from the managed list are stored on the customer.
        $this->assertSame(['loyal', 'follow-up'], $customer->fresh()->tags);
    }

    public function test_partner_can_tag_a_downstream_bo_customer_they_cannot_fully_edit(): void
    {
        $partner = Partner::factory()->create();
        $bo = BusinessOwner::factory()->create(['partner_id' => $partner->id]);
        $customer = Customer::factory()->forBusinessOwner($bo)->create(['category' => CustomerCategory::Customer]);

        CustomerTag::query()->create(['name' => 'high-value']);

        // Partner cannot fully update a BO-owned customer, but can tag it.
        $this->assertFalse(Gate::forUser($partner->user)->allows('update', $customer));
        $this->assertTrue(Gate::forUser($partner->user)->allows('tagLabels', $customer));

        $this->actingAs($partner->user);

        Livewire::test(ListCustomers::class)
            ->callTableAction('manageTags', $customer, data: ['tags' => ['high-value']]);

        $this->assertSame(['high-value'], $customer->fresh()->tags);
    }
}
