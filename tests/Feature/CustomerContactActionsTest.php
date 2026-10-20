<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Actions\CustomerContactActions;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerContactActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_action_builds_wa_me_url_and_opens_new_tab(): void
    {
        [, $partner, $businessOwner] = $this->businessOwnerUser('P130001', 'BO130001', 'Contact BO');
        $customer = $this->customer($businessOwner, $partner, 'C130001', '0123456789');

        $action = CustomerContactActions::whatsApp()->record($customer);

        $this->assertSame('https://wa.me/60123456789', $action->getUrl());
        $this->assertTrue($action->isVisible());
        $this->assertTrue($action->shouldOpenUrlInNewTab());
    }

    public function test_call_action_builds_tel_url(): void
    {
        [, $partner, $businessOwner] = $this->businessOwnerUser('P130002', 'BO130002', 'Call BO');
        $customer = $this->customer($businessOwner, $partner, 'C130002', '0123456789');

        $action = CustomerContactActions::call()->record($customer);

        $this->assertSame('tel:+60123456789', $action->getUrl());
        $this->assertTrue($action->isVisible());
    }

    public function test_actions_normalize_messy_numbers(): void
    {
        [, $partner, $businessOwner] = $this->businessOwnerUser('P130003', 'BO130003', 'Messy BO');
        $messy = $this->customer($businessOwner, $partner, 'C130003', '012-345 6789');

        $this->assertSame(
            'https://wa.me/60123456789',
            CustomerContactActions::whatsApp()->record($messy)->getUrl()
        );
    }

    public function test_actions_hidden_when_phone_blank(): void
    {
        [, $partner, $businessOwner] = $this->businessOwnerUser('P130004', 'BO130004', 'Blank BO');
        $blank = $this->customer($businessOwner, $partner, 'C130004', '');

        $this->assertFalse(CustomerContactActions::whatsApp()->record($blank)->isVisible());
        $this->assertFalse(CustomerContactActions::call()->record($blank)->isVisible());
    }

    public function test_business_owner_only_sees_own_customers_in_table(): void
    {
        [$aUser, $aPartner, $aBo] = $this->businessOwnerUser('P130005', 'BO130005', 'Owner A BO');
        $aCust = $this->customer($aBo, $aPartner, 'C130005', '0123456789');

        [, $bPartner, $bBo] = $this->businessOwnerUser('P130006', 'BO130006', 'Owner B BO');
        $bCust = $this->customer($bBo, $bPartner, 'C130006', '0123450000');

        $this->actingAs($aUser);

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$aCust])
            ->assertCanNotSeeTableRecords([$bCust]);
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

        $partner = Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => "{$name} Partner",
        ]);
        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => $name,
        ]);

        return [$user, $partner, $businessOwner];
    }

    private function customer(
        BusinessOwner $businessOwner,
        Partner $partner,
        string $customerNo,
        string $hpNo
    ): Customer {
        return Customer::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_no' => $customerNo,
            'category' => CustomerCategory::Customer,
            'name' => "Customer {$customerNo}",
            'hp_no' => $hpNo,
        ]);
    }
}
