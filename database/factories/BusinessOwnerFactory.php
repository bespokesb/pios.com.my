<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessOwner>
 */
class BusinessOwnerFactory extends Factory
{
    protected $model = BusinessOwner::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $negeri = fake()->randomElement([
            'Selangor', 'Johor', 'Pulau Pinang', 'Kuala Lumpur', 'Perak',
            'Kedah', 'Negeri Sembilan', 'Melaka', 'Pahang', 'Sabah', 'Sarawak',
        ]);

        return [
            'user_id' => User::factory()->state([
                'role' => UserRole::BusinessOwner,
                'status' => UserStatus::Active,
            ]),
            'partner_id' => Partner::factory(),
            'business_owner_no' => 'BO'.fake()->unique()->numerify('#####'),
            'applicant_name' => fake()->name(),
            'ic_no' => fake()->numerify('######-##-####'),
            'hp_no' => '601'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress(),
            'bandar' => fake()->city(),
            'negeri' => $negeri,
            'poskod' => fake()->numerify('#####'),
            'company_name' => fake()->randomElement(['Kedai', 'Niaga', 'Enterprise', 'Bakti', 'Sinar', 'Warisan', 'Perniagaan', 'Gerai'])
                .' '.fake()->lastName(),
            'product_description' => fake()->randomElement([
                'Menjual produk organik dan premium tempatan.',
                'Pembekal barangan runcit, herba dan makanan warisan.',
                'Menawarkan produk kesihatan dan makanan sihat.',
                'Peniaga produk kampung dan buatan tangan.',
            ]),
            'status' => RecordStatus::Active,
        ];
    }

    /**
     * A Business Owner awaiting HQ approval (inactive login).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RecordStatus::Pending,
            'user_id' => User::factory()->state([
                'role' => UserRole::BusinessOwner,
                'status' => UserStatus::Inactive,
            ]),
        ]);
    }

    /**
     * Assign the Business Owner to an existing partner.
     */
    public function forPartner(Partner $partner): static
    {
        return $this->state(fn (array $attributes): array => [
            'partner_id' => $partner->id,
        ]);
    }
}
