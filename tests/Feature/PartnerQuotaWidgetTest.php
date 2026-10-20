<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Widgets\PartnerQuotaWidget;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerQuotaWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_sees_own_quota_progress(): void
    {
        [$user, $partner] = $this->partnerUser('P000820', 'Widget Partner A');
        $businessOwner = $this->businessOwner($partner, 'BO000820', 'Widget BO A');

        for ($i = 0; $i < 7; $i++) {
            $this->customerAt($partner, $businessOwner, sprintf('C0082%02d', $i), Carbon::now());
        }

        $this->actingAs($user);

        Livewire::test(PartnerQuotaWidget::class)
            ->assertOk()
            ->assertSee('7 / 100');
    }

    public function test_widget_hidden_for_admin_without_partner(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($admin);

        $this->assertNull($admin->partner);
        $this->assertFalse(PartnerQuotaWidget::canView());
    }

    public function test_widget_counts_only_own_partner(): void
    {
        [$userA, $partnerA] = $this->partnerUser('P000821', 'Widget Partner A');
        $businessOwnerA = $this->businessOwner($partnerA, 'BO000821', 'Widget BO A');
        for ($i = 0; $i < 3; $i++) {
            $this->customerAt($partnerA, $businessOwnerA, sprintf('C0083%02d', $i), Carbon::now());
        }

        [, $partnerB] = $this->partnerUser('P000822', 'Widget Partner B');
        $businessOwnerB = $this->businessOwner($partnerB, 'BO000822', 'Widget BO B');
        for ($i = 0; $i < 5; $i++) {
            $this->customerAt($partnerB, $businessOwnerB, sprintf('C0084%02d', $i), Carbon::now());
        }

        $this->actingAs($userA);

        Livewire::test(PartnerQuotaWidget::class)
            ->assertOk()
            ->assertSee('3 / 100')
            ->assertDontSee('8 / 100');
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

        return [$user, $partner->refresh()];
    }

    private function businessOwner(Partner $partner, string $businessOwnerNo, string $name): BusinessOwner
    {
        return BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => $name,
        ]);
    }

    private function customerAt(
        Partner $partner,
        BusinessOwner $businessOwner,
        string $customerNo,
        \DateTimeInterface $createdAt
    ): Customer {
        $customer = Customer::query()->create([
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $partner->id,
            'customer_no' => $customerNo,
            'category' => CustomerCategory::Customer,
            'name' => "Customer {$customerNo}",
        ]);

        $customer->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $customer;
    }
}
