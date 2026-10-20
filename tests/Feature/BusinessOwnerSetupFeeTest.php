<?php

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\FeeSetting;
use App\Models\Partner;
use App\Models\User;
use App\Services\RegistrationApprover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessOwnerSetupFeeTest extends TestCase
{
    use RefreshDatabase;

    private function seedFeeSettings(): void
    {
        FeeSetting::query()->updateOrCreate(
            ['name' => 'Business Owner Setup Fee'],
            [
                'fee_type' => FeeType::BusinessOwnerSetup,
                'amount' => 10.00,
                'currency' => 'MYR',
                'effective_from' => now()->toDateString(),
                'status' => RecordStatus::Active,
            ],
        );

        FeeSetting::query()->updateOrCreate(
            ['name' => 'Business Owner Monthly Fee'],
            [
                'fee_type' => FeeType::BusinessOwnerMonthly,
                'amount' => 5.00,
                'currency' => 'MYR',
                'effective_from' => now()->toDateString(),
                'status' => RecordStatus::Active,
            ],
        );
    }

    public function test_active_amount_reads_from_fee_setting(): void
    {
        $this->assertNull(FeeSetting::activeAmount(FeeType::BusinessOwnerSetup));

        $this->seedFeeSettings();

        $this->assertSame('10.00', FeeSetting::activeAmount(FeeType::BusinessOwnerSetup));
        $this->assertSame('5.00', FeeSetting::activeAmount(FeeType::BusinessOwnerMonthly));

        FeeSetting::query()->where('name', 'Business Owner Setup Fee')->update(['amount' => 12.00]);

        $this->assertSame('12.00', FeeSetting::activeAmount(FeeType::BusinessOwnerSetup));
    }

    /**
     * @return array{0: Partner, 1: BusinessOwner}
     */
    private function pendingBusinessOwner(string $partnerNo, string $businessOwnerNo): array
    {
        $partner = Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => "{$businessOwnerNo} Partner",
            'status' => RecordStatus::Active,
        ]);

        $boUser = User::factory()->create([
            'role' => UserRole::BusinessOwner,
            'status' => UserStatus::Inactive,
        ]);

        $businessOwner = BusinessOwner::query()->create([
            'user_id' => $boUser->id,
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => "{$businessOwnerNo} Owner",
            'status' => RecordStatus::Pending,
        ]);

        return [$partner, $businessOwner];
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);
    }

    public function test_approving_business_owner_creates_one_setup_fee(): void
    {
        $this->seedFeeSettings();
        $admin = $this->adminUser();
        [$partner, $bo] = $this->pendingBusinessOwner('P000801', 'BO000801');

        $this->actingAs($admin);
        app(RegistrationApprover::class)->approveBusinessOwner($bo);

        $fees = Fee::query()
            ->where('business_owner_id', $bo->id)
            ->where('fee_type', FeeType::BusinessOwnerSetup)
            ->get();

        $this->assertCount(1, $fees);
        $fee = $fees->first();
        $this->assertSame(FeeStatus::Unpaid, $fee->status);
        $this->assertSame('10.00', $fee->amount);
        $this->assertSame($partner->id, $fee->partner_id);
        $this->assertNull($fee->billing_period);
        $this->assertNotNull($fee->due_date);
        $this->assertSame(RecordStatus::Active, $bo->fresh()->status);
    }

    public function test_reapproving_business_owner_does_not_duplicate_setup_fee(): void
    {
        $this->seedFeeSettings();
        $admin = $this->adminUser();
        [, $bo] = $this->pendingBusinessOwner('P000802', 'BO000802');

        $this->actingAs($admin);
        app(RegistrationApprover::class)->approveBusinessOwner($bo);
        app(RegistrationApprover::class)->approveBusinessOwner($bo->fresh());

        $this->assertSame(
            1,
            Fee::query()
                ->where('business_owner_id', $bo->id)
                ->where('fee_type', FeeType::BusinessOwnerSetup)
                ->count(),
        );
    }

    public function test_setup_fee_amount_comes_from_setting(): void
    {
        $this->seedFeeSettings();
        FeeSetting::query()->where('name', 'Business Owner Setup Fee')->update(['amount' => 12.00]);

        $admin = $this->adminUser();
        [, $bo] = $this->pendingBusinessOwner('P000803', 'BO000803');

        $this->actingAs($admin);
        app(RegistrationApprover::class)->approveBusinessOwner($bo);

        $fee = Fee::query()
            ->where('business_owner_id', $bo->id)
            ->where('fee_type', FeeType::BusinessOwnerSetup)
            ->first();

        $this->assertSame('12.00', $fee->amount);
    }

    public function test_setup_fee_is_audited_with_actor(): void
    {
        $this->seedFeeSettings();
        $admin = $this->adminUser();
        [, $bo] = $this->pendingBusinessOwner('P000804', 'BO000804');

        $this->actingAs($admin);
        app(RegistrationApprover::class)->approveBusinessOwner($bo);

        $fee = Fee::query()
            ->where('business_owner_id', $bo->id)
            ->where('fee_type', FeeType::BusinessOwnerSetup)
            ->first();

        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => Fee::class,
            'auditable_id' => $fee->id,
            'event' => 'fee_generated',
            'actor_id' => $admin->id,
        ]);
    }
}
