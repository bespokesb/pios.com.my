<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerVipTaggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_tag_downstream_customer_vip_then_non_vip(): void
    {
        [$partnerUser, $partner] = $this->partner('P000801');
        $customer = $this->customerUnder($partner, 'C000801');

        $this->assertTrue(Gate::forUser($partnerUser)->allows('tagVip', $customer));

        $this->actingAs($partnerUser);

        // Tag VIP → is_vip true, abo_no stored. Boolean flag only, no VIP number.
        Livewire::test(ListCustomers::class)
            ->callTableAction('tagVip', $customer, data: [
                'is_vip' => true,
                'abo_no' => 'ABO12345',
            ]);

        $customer->refresh();
        $this->assertTrue($customer->is_vip);
        $this->assertSame('ABO12345', $customer->abo_no);

        // Untag VIP → is_vip false.
        Livewire::test(ListCustomers::class)
            ->callTableAction('tagVip', $customer, data: [
                'is_vip' => false,
            ]);

        $customer->refresh();
        $this->assertFalse($customer->is_vip);
    }

    public function test_partner_cannot_tag_a_customer_outside_their_downstream(): void
    {
        [$partnerUser] = $this->partner('P000802');
        [, $otherPartner] = $this->partner('P000803');
        $foreign = $this->customerUnder($otherPartner, 'C000803');

        $this->assertFalse(Gate::forUser($partnerUser)->allows('tagVip', $foreign));
    }

    public function test_business_owner_cannot_tag_vip(): void
    {
        [, $partner] = $this->partner('P000804');
        $customer = $this->customerUnder($partner, 'C000804');

        $boUser = User::factory()->create(['role' => UserRole::BusinessOwner, 'status' => UserStatus::Active]);
        BusinessOwner::query()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000804',
            'applicant_name' => 'BO',
        ]);

        $this->assertFalse(Gate::forUser($boUser->fresh())->allows('tagVip', $customer));
    }

    public function test_partner_can_access_customer_resource_in_partner_panel(): void
    {
        [$partnerUser] = $this->partner('P000805');

        $this->assertTrue($partnerUser->canAccessPanel(filament()->getPanel('partner')));

        $routes = collect(\Route::getRoutes())->map(fn ($r): string => $r->uri())->all();
        $this->assertContains('partner/customers', $routes);
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

    private function customerUnder(Partner $partner, string $customerNo): Customer
    {
        $bo = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO'.$customerNo,
            'applicant_name' => 'BO '.$customerNo,
        ]);

        return Customer::query()->create([
            'business_owner_id' => $bo->id,
            'partner_id' => $partner->id,
            'customer_no' => $customerNo,
            'name' => 'Cust '.$customerNo,
            'category' => CustomerCategory::Customer,
        ]);
    }
}
