<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Services\RegistrationApprover;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Generates realistic demo data (partners, business owners, customers, products)
 * and proves the Partner::MAX_DIRECT_PARTNERS = 4 active-downline cap end to end
 * via the real RegistrationApprover.
 *
 * Run with:  php artisan db:seed --class=DemoDataSeeder
 * Refuses to run in production to avoid polluting live data.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoDataSeeder skipped: not intended for production.');

            return;
        }

        // RegistrationApprover audits every approval, so it needs an actor.
        Auth::login(User::system());

        $approver = app(RegistrationApprover::class);

        // 1) A sponsoring partner (HQ-recruited, already active).
        $root = Partner::factory()->create([
            'applicant_name' => 'Aiman Sponsor',
        ]);

        // 2) Approve sub-partners one by one through the real service. The first
        //    four succeed; the fifth must be rejected by the cap.
        $approved = 0;
        $blocked = 0;

        for ($i = 1; $i <= Partner::MAX_DIRECT_PARTNERS + 1; $i++) {
            $recruit = Partner::factory()->pending()->forParent($root)->create([
                'applicant_name' => "Sub-partner #{$i}",
            ]);

            try {
                $approver->approvePartner($recruit);
                $approved++;
            } catch (RegistrationApprovalException $e) {
                $blocked++;
                $this->command?->warn("Recruit #{$i} blocked by cap: {$e->getMessage()}");
            }
        }

        $this->command?->info(sprintf(
            'Max-4 check → approved %d, blocked %d. Root now has %d active sub-partners (cap %d).',
            $approved,
            $blocked,
            $root->activeChildPartnersCount(),
            Partner::MAX_DIRECT_PARTNERS,
        ));

        // 3) Build downstream data under the root + its approved sub-partners.
        $partners = $root->childPartners()
            ->where('status', RecordStatus::Active)
            ->get()
            ->prepend($root);

        foreach ($partners as $partner) {
            $businessOwners = BusinessOwner::factory()
                ->count(rand(1, 3))
                ->forPartner($partner)
                ->create();

            foreach ($businessOwners as $bo) {
                Customer::factory()->count(rand(3, 6))->forBusinessOwner($bo)->create();
                Customer::factory()->count(rand(1, 2))->vip()->forBusinessOwner($bo)->create();
                Customer::factory()->count(2)->lead()->forBusinessOwner($bo)->create();

                Product::factory()->count(rand(3, 5))->forBusinessOwner($bo)->create();
            }
        }

        Auth::logout();

        $this->command?->info(sprintf(
            'Demo data ready — partners: %d, business owners: %d, customers: %d, products: %d.',
            Partner::query()->count(),
            BusinessOwner::query()->count(),
            Customer::query()->count(),
            Product::query()->count(),
        ));
        $this->command?->info('All demo logins use password "password".');
    }
}
