<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerOwnedCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_creates_a_customer_owned_directly_with_no_business_owner(): void
    {
        [$partnerUser, $partner] = $this->partner('P000601');

        $this->actingAs($partnerUser);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'Direct Cust',
                'hp_no' => '0123456789',
                'category' => CustomerCategory::Lead->value,
                'status' => RecordStatus::Active->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->where('name', 'Direct Cust')->firstOrFail();
        $this->assertNull($customer->business_owner_id);
        $this->assertSame($partner->id, $customer->partner_id);
        $this->assertNotEmpty($customer->customer_no);
    }

    public function test_partner_can_manage_their_own_customer_but_not_a_business_owner_customer(): void
    {
        [$partnerUser, $partner] = $this->partner('P000602');

        $own = Customer::factory()->forPartner($partner)->create();

        $bo = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000602',
            'applicant_name' => 'BO',
        ]);
        $boCustomer = Customer::factory()->forBusinessOwner($bo)->create();

        // Full control over the partner-direct customer.
        $this->assertTrue(Gate::forUser($partnerUser)->allows('update', $own));
        $this->assertTrue(Gate::forUser($partnerUser)->allows('delete', $own));

        // BO customer stays read-only to the partner (VIP tagging is the only write path).
        $this->assertFalse(Gate::forUser($partnerUser)->allows('update', $boCustomer));
        $this->assertFalse(Gate::forUser($partnerUser)->allows('delete', $boCustomer));
        $this->assertTrue(Gate::forUser($partnerUser)->allows('tagVip', $boCustomer));
        $this->assertTrue(Gate::forUser($partnerUser)->allows('view', $boCustomer));
    }

    public function test_partner_sees_both_their_own_and_downstream_business_owner_customers(): void
    {
        [, $partner] = $this->partner('P000603');
        $partnerUser = $partner->user;

        $own = Customer::factory()->forPartner($partner)->create();

        $bo = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000603',
            'applicant_name' => 'BO',
        ]);
        $boCustomer = Customer::factory()->forBusinessOwner($bo)->create();

        [, $otherPartner] = $this->partner('P000604');
        $foreign = Customer::factory()->forPartner($otherPartner)->create();

        $visibleIds = Customer::query()->visibleTo($partnerUser)->pluck('id');

        $this->assertTrue($visibleIds->contains($own->id));
        $this->assertTrue($visibleIds->contains($boCustomer->id));
        $this->assertFalse($visibleIds->contains($foreign->id));
    }

    /** @return array{0: User, 1: Partner} */
    private function partner(string $partnerNo): array
    {
        $user = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $partner = Partner::query()->create([
            'user_id' => $user->id,
            'partner_no' => $partnerNo,
            'applicant_name' => 'Partner '.$partnerNo,
        ]);

        return [$user->fresh(), $partner];
    }
}
