<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerSegmentsAndStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_tab_badges_are_scoped_to_the_business_owner(): void
    {
        [$bo1User, $p1, $bo1] = $this->businessOwnerUser('P120001', 'BO120001', 'Seg BO1');
        $this->customer($bo1, $p1, 'C120001', CustomerCategory::Lead);
        $this->customer($bo1, $p1, 'C120002', CustomerCategory::Lead);
        $this->customer($bo1, $p1, 'C120003', CustomerCategory::Prospect);
        $this->customer($bo1, $p1, 'C120004', CustomerCategory::Customer);

        [, $p2, $bo2] = $this->businessOwnerUser('P120002', 'BO120002', 'Seg BO2');
        $this->customer($bo2, $p2, 'C120005', CustomerCategory::Lead);
        $this->customer($bo2, $p2, 'C120006', CustomerCategory::Lead);
        $this->customer($bo2, $p2, 'C120007', CustomerCategory::Lead);

        $this->actingAs($bo1User);

        $tabs = Livewire::test(ListCustomers::class)->instance()->getTabs();

        // Filament v5 getBadge() returns a string.
        $this->assertSame('4', $tabs['all']->getBadge());
        $this->assertSame('2', $tabs['lead']->getBadge());
        $this->assertSame('1', $tabs['prospect']->getBadge());
        $this->assertSame('1', $tabs['customer']->getBadge());
        $this->assertSame('0', $tabs['vip']->getBadge());
    }

    public function test_active_tab_filters_the_table(): void
    {
        [$bo1User, $p1, $bo1] = $this->businessOwnerUser('P120010', 'BO120010', 'Filter BO');
        $this->customer($bo1, $p1, 'C120010', CustomerCategory::Lead);
        $this->customer($bo1, $p1, 'C120011', CustomerCategory::Lead);
        $this->customer($bo1, $p1, 'C120012', CustomerCategory::Prospect);
        $this->customer($bo1, $p1, 'C120013', CustomerCategory::Customer);

        $this->actingAs($bo1User);

        Livewire::test(ListCustomers::class)
            ->assertCountTableRecords(4)
            ->set('activeTab', 'lead')
            ->assertCountTableRecords(2)
            ->set('activeTab', 'prospect')
            ->assertCountTableRecords(1);
    }

    public function test_table_searches_name_and_phone(): void
    {
        [$bo1User, $p1, $bo1] = $this->businessOwnerUser('P120020', 'BO120020', 'Search BO');
        $alice = $this->customer($bo1, $p1, 'C120020', CustomerCategory::Lead);
        $alice->update(['name' => 'Alice Tan', 'hp_no' => '60139990001']);
        $bob = $this->customer($bo1, $p1, 'C120021', CustomerCategory::Lead);
        $bob->update(['name' => 'Bob Lee', 'hp_no' => '60139990002']);

        $this->actingAs($bo1User);

        Livewire::test(ListCustomers::class)
            ->searchTable('Alice')
            ->assertCanSeeTableRecords([$alice])
            ->assertCanNotSeeTableRecords([$bob]);

        Livewire::test(ListCustomers::class)
            ->searchTable('60139990002')
            ->assertCanSeeTableRecords([$bob])
            ->assertCanNotSeeTableRecords([$alice]);
    }

    public function test_business_owner_can_change_own_customer_stage(): void
    {
        [$bo1User, $p1, $bo1] = $this->businessOwnerUser('P120030', 'BO120030', 'Stage BO');
        $lead = $this->customer($bo1, $p1, 'C120030', CustomerCategory::Lead);

        $this->actingAs($bo1User);

        Livewire::test(ListCustomers::class)
            ->callTableAction('setStage', $lead, data: ['category' => CustomerCategory::Customer->value]);

        $this->assertSame(CustomerCategory::Customer, $lead->fresh()->category);
        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => Customer::class,
            'auditable_id' => $lead->id,
            'event' => 'updated',
        ]);
    }

    public function test_set_stage_rejects_a_non_stage_value(): void
    {
        [$bo1User, $p1, $bo1] = $this->businessOwnerUser('P120040', 'BO120040', 'Reject Stage BO');
        $lead = $this->customer($bo1, $p1, 'C120040', CustomerCategory::Lead);

        $this->actingAs($bo1User);

        Livewire::test(ListCustomers::class)
            ->callTableAction('setStage', $lead, data: ['category' => 'not_a_stage']);

        // Only Lead/Prospect/Customer are settable — an invalid value is rejected.
        $this->assertSame(CustomerCategory::Lead, $lead->fresh()->category);
    }

    public function test_set_stage_stays_available_for_a_vip_customer(): void
    {
        // VIP is now an orthogonal boolean, so a VIP customer still has a settable stage.
        [$bo1User, $p1, $bo1] = $this->businessOwnerUser('P120050', 'BO120050', 'VIP Stage BO');
        $vip = $this->customer($bo1, $p1, 'C120050', CustomerCategory::Customer, isVip: true);

        $this->actingAs($bo1User);

        Livewire::test(ListCustomers::class)
            ->assertTableActionVisible('setStage', $vip);
    }

    public function test_business_owner_cannot_see_or_update_another_business_owners_customer(): void
    {
        [$bo1User, $p1, $bo1] = $this->businessOwnerUser('P120060', 'BO120060', 'Owner BO');
        [, $p2, $bo2] = $this->businessOwnerUser('P120061', 'BO120061', 'Foreign BO');
        $foreign = $this->customer($bo2, $p2, 'C120060', CustomerCategory::Lead);

        $this->actingAs($bo1User);

        Livewire::test(ListCustomers::class)
            ->assertCanNotSeeTableRecords([$foreign]);

        $this->assertTrue(Gate::forUser($bo1User)->denies('update', $foreign));
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
}
