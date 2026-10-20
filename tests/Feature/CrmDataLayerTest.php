<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\CustomerNote;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CrmDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_create_note_and_follow_up_on_own_customer(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P010001', 'BO010001', 'CRM BO');
        $cust = $this->customer($bo, $partner, 'C010001', CustomerCategory::Customer);

        $note = CustomerNote::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'body' => 'Called, interested',
            'created_by' => $boUser->id,
        ]);

        $followUp = CustomerFollowUp::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'note' => 'Call back',
            'due_on' => now('Asia/Kuala_Lumpur')->addDay()->toDateString(),
            'created_by' => $boUser->id,
        ]);

        $this->assertSame($bo->id, $note->business_owner_id);
        $this->assertSame($cust->id, $note->customer_id);
        $this->assertSame($bo->id, $followUp->business_owner_id);
        $this->assertSame($cust->id, $followUp->customer_id);
    }

    public function test_visible_to_isolates_other_business_owner(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P010001', 'BO010001', 'CRM BO');
        $cust = $this->customer($bo, $partner, 'C010001', CustomerCategory::Customer);

        CustomerNote::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'body' => 'Owner note',
            'created_by' => $boUser->id,
        ]);
        CustomerFollowUp::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'note' => 'Owner follow-up',
            'due_on' => now('Asia/Kuala_Lumpur')->addDay()->toDateString(),
            'created_by' => $boUser->id,
        ]);

        [$otherBoUser] = $this->businessOwnerUser('P010002', 'BO010002', 'Other CRM BO');

        $this->assertSame(1, CustomerNote::query()->visibleTo($boUser)->count());
        $this->assertSame(0, CustomerNote::query()->visibleTo($otherBoUser)->count());
        $this->assertSame(1, CustomerFollowUp::query()->visibleTo($boUser)->count());
        $this->assertSame(0, CustomerFollowUp::query()->visibleTo($otherBoUser)->count());
    }

    public function test_policies_deny_cross_business_owner_view(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P010001', 'BO010001', 'CRM BO');
        $cust = $this->customer($bo, $partner, 'C010001', CustomerCategory::Customer);

        $note = CustomerNote::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'body' => 'Owner note',
            'created_by' => $boUser->id,
        ]);
        $followUp = CustomerFollowUp::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'note' => 'Owner follow-up',
            'due_on' => now('Asia/Kuala_Lumpur')->addDay()->toDateString(),
            'created_by' => $boUser->id,
        ]);

        [$otherBoUser] = $this->businessOwnerUser('P010002', 'BO010002', 'Other CRM BO');

        $this->assertTrue(Gate::forUser($boUser)->allows('view', $note));
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('view', $note));
        $this->assertTrue(Gate::forUser($boUser)->allows('view', $followUp));
        $this->assertFalse(Gate::forUser($otherBoUser)->allows('view', $followUp));
    }

    public function test_follow_up_overdue_computation(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P010001', 'BO010001', 'CRM BO');
        $cust = $this->customer($bo, $partner, 'C010001', CustomerCategory::Customer);

        $base = [
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'created_by' => $boUser->id,
        ];

        $overdue = CustomerFollowUp::query()->create($base + [
            'note' => 'Overdue',
            'due_on' => now('Asia/Kuala_Lumpur')->subDay()->toDateString(),
            'completed_at' => null,
        ]);
        $today = CustomerFollowUp::query()->create($base + [
            'note' => 'Due today',
            'due_on' => now('Asia/Kuala_Lumpur')->toDateString(),
            'completed_at' => null,
        ]);
        $done = CustomerFollowUp::query()->create($base + [
            'note' => 'Completed',
            'due_on' => now('Asia/Kuala_Lumpur')->subDays(3)->toDateString(),
            'completed_at' => now(),
        ]);

        $this->assertTrue($overdue->fresh()->isOverdue());
        $this->assertFalse($today->fresh()->isOverdue());
        $this->assertFalse($done->fresh()->isOverdue());

        $this->assertSame(1, CustomerFollowUp::query()->overdue()->count());
    }

    public function test_admin_sees_all_child_records(): void
    {
        [$boUser, $partner, $bo] = $this->businessOwnerUser('P010001', 'BO010001', 'CRM BO');
        $cust = $this->customer($bo, $partner, 'C010001', CustomerCategory::Customer);

        $note = CustomerNote::query()->create([
            'customer_id' => $cust->id,
            'business_owner_id' => $bo->id,
            'body' => 'Owner note',
            'created_by' => $boUser->id,
        ]);

        $admin = $this->adminUser();

        $this->assertSame(1, CustomerNote::query()->visibleTo($admin)->count());
        $this->assertTrue(Gate::forUser($admin)->allows('view', $note));
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
            'is_vip' => $isVip,
            'category' => $category,
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
