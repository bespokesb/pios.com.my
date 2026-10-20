<?php

namespace Tests\Feature;

use App\Enums\CustomerCategory;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Services\PartnerQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PartnerQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    public function test_counts_only_this_partners_customers_created_this_month(): void
    {
        [$partnerA, $businessOwnerA] = $this->partnerAndBusinessOwner('P000801', 'BO000801', 'Quota Partner A');

        // 3 customers for A this MYT month.
        $this->customerAt($partnerA, $businessOwnerA, 'C000801', Carbon::now());
        $this->customerAt($partnerA, $businessOwnerA, 'C000802', Carbon::now());
        $this->customerAt($partnerA, $businessOwnerA, 'C000803', Carbon::now());

        // 1 customer for A in the previous month (last instant of prev month) — excluded.
        $this->customerAt(
            $partnerA,
            $businessOwnerA,
            'C000804',
            Carbon::now('Asia/Kuala_Lumpur')->startOfMonth()->subSecond()
        );

        // Partner B with 2 customers this month — excluded from A's count.
        [$partnerB, $businessOwnerB] = $this->partnerAndBusinessOwner('P000802', 'BO000802', 'Quota Partner B');
        $this->customerAt($partnerB, $businessOwnerB, 'C000805', Carbon::now());
        $this->customerAt($partnerB, $businessOwnerB, 'C000806', Carbon::now());

        $this->assertSame(3, (new PartnerQuotaService)->monthToDateCount($partnerA));
    }

    public function test_first_instant_of_month_included_last_instant_of_prev_month_excluded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', 'Asia/Kuala_Lumpur'));

        [$partner, $businessOwner] = $this->partnerAndBusinessOwner('P000803', 'BO000803', 'Boundary Partner');

        // First instant of this MYT month — counted.
        $this->customerAt(
            $partner,
            $businessOwner,
            'C000810',
            Carbon::parse('2026-06-01 00:00:00', 'Asia/Kuala_Lumpur')
        );

        // Last instant of previous MYT month — NOT counted.
        $this->customerAt(
            $partner,
            $businessOwner,
            'C000811',
            Carbon::parse('2026-05-31 23:59:59', 'Asia/Kuala_Lumpur')
        );

        $this->assertSame(1, (new PartnerQuotaService)->monthToDateCount($partner));
    }

    public function test_target_is_configurable(): void
    {
        [$partner] = $this->partnerAndBusinessOwner('P000804', 'BO000804', 'Target Partner');

        $service = new PartnerQuotaService;

        $this->assertSame(100, $service->attainment($partner)['target']);

        config(['pios.partner_monthly_customer_target' => 250]);

        $this->assertSame(250, $service->attainment($partner)['target']);
    }

    public function test_attainment_percent_computed_correctly(): void
    {
        [$partner, $businessOwner] = $this->partnerAndBusinessOwner('P000805', 'BO000805', 'Attainment Partner');

        for ($i = 0; $i < 25; $i++) {
            $this->customerAt($partner, $businessOwner, sprintf('C0009%02d', $i), Carbon::now());
        }

        $service = new PartnerQuotaService;

        $this->assertSame(
            ['count' => 25, 'target' => 100, 'percent' => 25],
            $service->attainment($partner)
        );

        // Div-by-zero guard: target 0 → percent 0.
        config(['pios.partner_monthly_customer_target' => 0]);

        $this->assertSame(0, $service->attainment($partner)['percent']);
    }

    /**
     * @return array{0: Partner, 1: BusinessOwner}
     */
    private function partnerAndBusinessOwner(string $partnerNo, string $businessOwnerNo, string $name): array
    {
        $partner = Partner::query()->create([
            'partner_no' => $partnerNo,
            'applicant_name' => "{$name} Partner",
        ]);

        $businessOwner = BusinessOwner::query()->create([
            'partner_id' => $partner->id,
            'business_owner_no' => $businessOwnerNo,
            'applicant_name' => $name,
        ]);

        return [$partner, $businessOwner];
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
