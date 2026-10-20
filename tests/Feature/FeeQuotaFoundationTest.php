<?php

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\SystemUserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeQuotaFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusinessOwner(): BusinessOwner
    {
        $partner = Partner::query()->create([
            'partner_no' => 'P000020',
            'applicant_name' => 'Quota Partner',
        ]);

        return BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => 'BO000020',
            'applicant_name' => 'Quota Business Owner',
        ]);
    }

    public function test_duplicate_billing_period_fee_is_rejected_by_unique_index(): void
    {
        $bo = $this->makeBusinessOwner();

        Fee::query()->create([
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => '2026-06',
            'amount' => 5,
            'status' => FeeStatus::Unpaid,
        ]);

        $this->expectException(QueryException::class);

        Fee::query()->create([
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => '2026-06',
            'amount' => 5,
            'status' => FeeStatus::Unpaid,
        ]);
    }

    public function test_first_or_create_on_same_triple_yields_exactly_one_fee(): void
    {
        $bo = $this->makeBusinessOwner();

        $key = [
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => '2026-06',
        ];

        Fee::query()->firstOrCreate($key, ['amount' => 5, 'status' => FeeStatus::Unpaid]);
        Fee::query()->firstOrCreate($key, ['amount' => 5, 'status' => FeeStatus::Unpaid]);

        $this->assertSame(1, Fee::query()->count());
    }

    public function test_nullable_billing_period_allows_multiple_non_recurring_fees(): void
    {
        $bo = $this->makeBusinessOwner();

        Fee::query()->create([
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerSetup,
            'billing_period' => null,
            'amount' => 10,
            'status' => FeeStatus::Unpaid,
        ]);

        Fee::query()->create([
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerSetup,
            'billing_period' => null,
            'amount' => 10,
            'status' => FeeStatus::Unpaid,
        ]);

        $this->assertSame(2, Fee::query()->where('business_owner_id', $bo->id)->count());
    }

    public function test_business_owner_setup_fee_type_exists(): void
    {
        $this->assertSame('business_owner_setup', FeeType::BusinessOwnerSetup->value);
        $this->assertSame('Business Owner Setup', FeeType::BusinessOwnerSetup->label());
        $this->assertArrayHasKey('business_owner_setup', FeeType::options());
    }

    public function test_system_user_seeder_is_idempotent_and_non_login(): void
    {
        (new SystemUserSeeder)->run();
        (new SystemUserSeeder)->run();

        $this->assertSame(
            1,
            User::query()->where('email', 'system@pios.com.my')->count(),
        );

        $system = User::system();
        $this->assertSame(UserStatus::Inactive, $system->status);
        $this->assertFalse($system->canAccessPanel(filament()->getPanel('admin')));
        $this->assertTrue(User::system()->is(User::system()));
    }

    public function test_application_timezone_is_kuala_lumpur(): void
    {
        $this->assertSame('Asia/Kuala_Lumpur', config('app.timezone'));
    }
}
