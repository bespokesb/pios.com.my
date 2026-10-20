<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\OperationalReports;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PartnerQuotaReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_hq_report_lists_each_partner_attainment(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $partnerA = $this->partner('P000830', 'Report Partner A');
        $businessOwnerA = $this->businessOwner($partnerA, 'BO000830', 'Report BO A');
        for ($i = 0; $i < 4; $i++) {
            $this->customerAt($partnerA, $businessOwnerA, sprintf('C0085%02d', $i), Carbon::now());
        }
        // One A-customer dated last month — must be excluded.
        $this->customerAt($partnerA, $businessOwnerA, 'C008599', Carbon::now()->subMonthNoOverflow()->startOfMonth());

        $partnerB = $this->partner('P000831', 'Report Partner B');
        $businessOwnerB = $this->businessOwner($partnerB, 'BO000831', 'Report BO B');
        $this->customerAt($partnerB, $businessOwnerB, 'C008600', Carbon::now());

        $this->actingAs($admin);

        $page = new OperationalReports;
        $rows = collect($page->getPartnerQuotaAttainmentProperty())->keyBy('partner_id');

        $this->assertSame(4, $rows[$partnerA->id]['count']);
        $this->assertSame(100, $rows[$partnerA->id]['target']);
        $this->assertSame(4, $rows[$partnerA->id]['percent']);

        $this->assertSame(1, $rows[$partnerB->id]['count']);
        $this->assertSame(100, $rows[$partnerB->id]['target']);
        $this->assertSame(1, $rows[$partnerB->id]['percent']);
    }

    public function test_hq_attainment_uses_single_grouped_query(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SysAdmin,
            'status' => UserStatus::Active,
        ]);

        $partnerA = $this->partner('P000832', 'Report Partner A');
        $businessOwnerA = $this->businessOwner($partnerA, 'BO000832', 'Report BO A');
        for ($i = 0; $i < 3; $i++) {
            $this->customerAt($partnerA, $businessOwnerA, sprintf('C0087%02d', $i), Carbon::now());
        }

        $partnerB = $this->partner('P000833', 'Report Partner B');
        $businessOwnerB = $this->businessOwner($partnerB, 'BO000833', 'Report BO B');
        $this->customerAt($partnerB, $businessOwnerB, 'C008800', Carbon::now());

        $this->actingAs($admin);

        $page = new OperationalReports;

        DB::flushQueryLog();
        DB::enableQueryLog();

        $page->getPartnerQuotaAttainmentProperty();

        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $groupedQueries = collect($log)
            ->filter(fn (array $entry): bool => str_contains(strtolower($entry['query']), 'group by'))
            ->count();

        // Exactly one grouped COUNT keyed by partner_id — no per-partner N+1.
        $this->assertSame(1, $groupedQueries);
    }

    private function partner(string $partnerNo, string $name): Partner
    {
        $user = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        return Partner::query()->create([
            'user_id' => $user->id,
            'partner_no' => $partnerNo,
            'applicant_name' => $name,
        ])->refresh();
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
