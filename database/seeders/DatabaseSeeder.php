<?php

namespace Database\Seeders;

use App\Enums\FeeType;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\FeeSetting;
use App\Models\IdentifierSequence;
use App\Models\Partner;
use App\Models\PartnerTier;
use App\Models\ProductType;
use App\Models\Territory;
use App\Models\User;
use App\Services\BusinessOwnerUpgradeService;
use App\Services\IdentifierGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SystemUserSeeder::class);

        foreach (IdentifierGenerator::defaults() as $key => $defaults) {
            IdentifierSequence::query()->updateOrCreate(
                ['key' => $key],
                [
                    'prefix' => $defaults['prefix'],
                    'padding' => $defaults['padding'],
                    'next_number' => 1,
                ],
            );
        }

        Territory::query()->updateOrCreate(
            ['name' => 'Malaysia'],
            [
                'coverage_area' => 'Malaysia',
                'status' => RecordStatus::Active,
            ],
        );

        foreach ([
            ['name' => 'Standard', 'code' => 'standard', 'sort_order' => 10],
            ['name' => 'Premium', 'code' => 'premium', 'sort_order' => 20],
        ] as $tier) {
            PartnerTier::query()->updateOrCreate(
                ['code' => $tier['code']],
                $tier + ['status' => RecordStatus::Active],
            );
        }

        // Product categories (surfaced to users as "Category").
        foreach ([
            ['name' => 'Health Product', 'code' => 'health_product'],
            ['name' => 'Services', 'code' => 'services'],
            ['name' => 'Book', 'code' => 'book'],
        ] as $type) {
            ProductType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type + ['status' => RecordStatus::Active],
            );
        }

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

        // Canonical login users — one per role, all @pios.com.my, password "password".
        User::query()->updateOrCreate(
            ['email' => 'superadmin@pios.com.my'],
            [
                'name' => 'PIOS Super Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::SysAdmin,
                'status' => UserStatus::Active,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'owner@pios.com.my'],
            [
                'name' => 'PIOS Owner',
                'password' => Hash::make('password'),
                'role' => UserRole::PiosOwner,
                'status' => UserStatus::Active,
            ],
        );

        $ids = app(IdentifierGenerator::class);
        $standardTierId = PartnerTier::query()->where('code', 'standard')->value('id');

        // Partner (with a proper Partner profile).
        $partnerUser = User::query()->updateOrCreate(
            ['email' => 'partner@pios.com.my'],
            [
                'name' => fake()->name(),
                'password' => Hash::make('password'),
                'role' => UserRole::Partner,
                'status' => UserStatus::Active,
            ],
        );
        $partner = Partner::query()->where('user_id', $partnerUser->id)->first()
            ?? Partner::query()->create([
                'user_id' => $partnerUser->id,
                'partner_tier_id' => $standardTierId,
                'partner_no' => $ids->generate('partner', Partner::class, 'partner_no'),
                'applicant_name' => $partnerUser->name,
                'ic_no' => fake()->numerify('######-##-####'),
                'hp_no' => '601'.fake()->numerify('########'),
                'email' => $partnerUser->email,
                'address' => fake()->streetAddress(),
                'bandar' => fake()->city(),
                'negeri' => 'Selangor',
                'poskod' => fake()->numerify('#####'),
                'status' => RecordStatus::Active,
            ]);

        // A Partner is a Business Owner by default — give partner@ a BO profile too.
        app(BusinessOwnerUpgradeService::class)->provisionFor($partner);

        // A standalone Business Owner (the role that can later activate INTO a partner).
        $boUser = User::query()->updateOrCreate(
            ['email' => 'businessowner@pios.com.my'],
            [
                'name' => fake()->name(),
                'password' => Hash::make('password'),
                'role' => UserRole::BusinessOwner,
                'status' => UserStatus::Active,
            ],
        );
        BusinessOwner::query()->where('user_id', $boUser->id)->first()
            ?? BusinessOwner::query()->create([
                'user_id' => $boUser->id,
                'partner_id' => $partner->id,
                'business_owner_no' => $ids->generate('business_owner', BusinessOwner::class, 'business_owner_no'),
                'applicant_name' => $boUser->name,
                'ic_no' => fake()->numerify('######-##-####'),
                'hp_no' => '601'.fake()->numerify('########'),
                'email' => $boUser->email,
                'address' => fake()->streetAddress(),
                'bandar' => fake()->city(),
                'negeri' => 'Selangor',
                'poskod' => fake()->numerify('#####'),
                'company_name' => fake()->company(),
                'status' => RecordStatus::Active,
            ]);
    }
}
