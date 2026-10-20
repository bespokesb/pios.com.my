<?php

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\RecordStatus;
use App\Models\AuditLog;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\FeeSetting;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyBusinessOwnerFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMonthlyFeeSetting('5.00');
    }

    public function test_command_generates_one_monthly_fee_per_active_business_owner(): void
    {
        $partner = $this->partner('P000101');
        $boA = $this->businessOwner($partner, 'BO000101', RecordStatus::Active);
        $boB = $this->businessOwner($partner, 'BO000102', RecordStatus::Active);

        $this->artisan('fees:generate-monthly')->assertSuccessful();

        $period = now()->format('Y-m');

        $this->assertSame(
            2,
            Fee::query()
                ->where('fee_type', FeeType::BusinessOwnerMonthly)
                ->where('billing_period', $period)
                ->count(),
        );

        foreach ([$boA, $boB] as $bo) {
            $fee = Fee::query()
                ->where('business_owner_id', $bo->id)
                ->where('fee_type', FeeType::BusinessOwnerMonthly)
                ->where('billing_period', $period)
                ->firstOrFail();

            $this->assertSame('5.00', $fee->amount);
            $this->assertSame(FeeStatus::Unpaid, $fee->status);
            $this->assertSame($partner->id, $fee->partner_id);
            $this->assertSame($period, $fee->billing_period);
            $this->assertNotNull($fee->due_date);
        }
    }

    public function test_running_command_twice_does_not_duplicate(): void
    {
        $partner = $this->partner('P000103');
        $this->businessOwner($partner, 'BO000103', RecordStatus::Active);

        $this->artisan('fees:generate-monthly')->assertSuccessful();
        $this->artisan('fees:generate-monthly')->assertSuccessful();

        $period = now()->format('Y-m');

        $this->assertSame(
            1,
            Fee::query()
                ->where('fee_type', FeeType::BusinessOwnerMonthly)
                ->where('billing_period', $period)
                ->count(),
        );
    }

    public function test_inactive_business_owners_are_not_billed(): void
    {
        $partner = $this->partner('P000104');
        $active = $this->businessOwner($partner, 'BO000104', RecordStatus::Active);
        $this->businessOwner($partner, 'BO000105', RecordStatus::Pending);
        $this->businessOwner($partner, 'BO000106', RecordStatus::Suspended);
        $this->businessOwner($partner, 'BO000107', RecordStatus::Inactive);

        $this->artisan('fees:generate-monthly')->assertSuccessful();

        $period = now()->format('Y-m');

        $this->assertSame(
            1,
            Fee::query()
                ->where('fee_type', FeeType::BusinessOwnerMonthly)
                ->where('billing_period', $period)
                ->count(),
        );

        $this->assertSame(
            1,
            Fee::query()
                ->where('business_owner_id', $active->id)
                ->where('billing_period', $period)
                ->count(),
        );
    }

    public function test_amount_comes_from_fee_setting(): void
    {
        $this->seedMonthlyFeeSetting('7.00');

        $partner = $this->partner('P000108');
        $bo = $this->businessOwner($partner, 'BO000108', RecordStatus::Active);

        $this->artisan('fees:generate-monthly')->assertSuccessful();

        $fee = Fee::query()
            ->where('business_owner_id', $bo->id)
            ->where('fee_type', FeeType::BusinessOwnerMonthly)
            ->firstOrFail();

        $this->assertSame('7.00', $fee->amount);
    }

    public function test_period_option_backfills_a_past_month_idempotently(): void
    {
        $partner = $this->partner('P000109');
        $bo = $this->businessOwner($partner, 'BO000109', RecordStatus::Active);

        $this->artisan('fees:generate-monthly', ['--period' => '2026-01'])->assertSuccessful();
        $this->artisan('fees:generate-monthly', ['--period' => '2026-01'])->assertSuccessful();

        $fees = Fee::query()
            ->where('business_owner_id', $bo->id)
            ->where('fee_type', FeeType::BusinessOwnerMonthly)
            ->where('billing_period', '2026-01')
            ->get();

        $this->assertCount(1, $fees);
        $this->assertSame('2026-01-31', $fees->first()->due_date->toDateString());
    }

    public function test_audit_actor_is_system_user(): void
    {
        $partner = $this->partner('P000110');
        $bo = $this->businessOwner($partner, 'BO000110', RecordStatus::Active);

        $this->artisan('fees:generate-monthly')->assertSuccessful();

        $fee = Fee::query()
            ->where('business_owner_id', $bo->id)
            ->where('fee_type', FeeType::BusinessOwnerMonthly)
            ->firstOrFail();

        $this->assertDatabaseHas(AuditLog::class, [
            'auditable_type' => Fee::class,
            'auditable_id' => $fee->id,
            'event' => 'fee_generated',
            'actor_id' => User::system()->id,
        ]);
    }

    public function test_overdue_is_computed(): void
    {
        $partner = $this->partner('P000111');
        $bo = $this->businessOwner($partner, 'BO000111', RecordStatus::Active);

        $pastDue = Fee::query()->create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => '2026-04',
            'amount' => 5,
            'due_date' => now()->copy()->subMonth()->toDateString(),
            'status' => FeeStatus::Unpaid,
        ]);

        $futureDue = Fee::query()->create([
            'partner_id' => $partner->id,
            'business_owner_id' => $bo->id,
            'fee_type' => FeeType::BusinessOwnerMonthly,
            'billing_period' => '2026-12',
            'amount' => 5,
            'due_date' => now()->copy()->addMonth()->toDateString(),
            'status' => FeeStatus::Unpaid,
        ]);

        // Mirror the FeesTable "overdue" filter predicate.
        $overdueIds = Fee::query()
            ->whereIn('status', [FeeStatus::Unpaid->value, FeeStatus::Pending->value])
            ->whereDate('due_date', '<', now()->toDateString())
            ->pluck('id')
            ->all();

        $this->assertContains($pastDue->id, $overdueIds);
        $this->assertNotContains($futureDue->id, $overdueIds);
    }

    private function seedMonthlyFeeSetting(string $amount): void
    {
        FeeSetting::query()->updateOrCreate(
            ['name' => 'Business Owner Monthly Fee'],
            [
                'fee_type' => FeeType::BusinessOwnerMonthly,
                'amount' => $amount,
                'currency' => 'MYR',
                'effective_from' => now()->toDateString(),
                'status' => RecordStatus::Active,
            ],
        );
    }

    private function partner(string $partnerNo): Partner
    {
        return Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => "{$partnerNo} Partner",
        ]);
    }

    private function businessOwner(Partner $partner, string $businessOwnerNo, RecordStatus $status): BusinessOwner
    {
        return BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => "{$businessOwnerNo} BO",
            'status' => $status,
        ]);
    }
}
