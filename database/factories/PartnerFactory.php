<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

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
                'role' => UserRole::Partner,
                'status' => UserStatus::Active,
            ]),
            'parent_partner_id' => null,
            'partner_no' => 'P'.fake()->unique()->numerify('#####'),
            'applicant_name' => fake()->name(),
            'ic_no' => fake()->numerify('######-##-####'),
            'hp_no' => '601'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress(),
            'bandar' => fake()->city(),
            'negeri' => $negeri,
            'poskod' => fake()->numerify('#####'),
            'status' => RecordStatus::Active,
        ];
    }

    /**
     * A recruit that has not yet been approved by HQ.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RecordStatus::Pending,
            'user_id' => User::factory()->state([
                'role' => UserRole::Partner,
                'status' => UserStatus::Inactive,
            ]),
        ]);
    }

    /**
     * A sub-partner recruited under the given sponsoring partner.
     */
    public function forParent(Partner $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_partner_id' => $parent->id,
        ]);
    }
}
