<?php

namespace Database\Factories;

use App\Enums\CustomerCategory;
use App\Enums\RecordStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_owner_id' => BusinessOwner::factory(),
            // Keep partner_id consistent with the owning Business Owner's partner.
            'partner_id' => fn (array $attributes): int => BusinessOwner::query()
                ->whereKey($attributes['business_owner_id'])
                ->value('partner_id'),
            'customer_no' => 'C'.fake()->unique()->numerify('#####'),
            'category' => CustomerCategory::Customer,
            'name' => fake()->name(),
            'nick_name' => fake()->optional()->firstName(),
            'hp_no' => '601'.fake()->numerify('########'),
            'kp_no' => fake()->numerify('######-##-####'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->streetAddress(),
            'status' => RecordStatus::Active,
        ];
    }

    /**
     * A VIP (LOS) customer.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => CustomerCategory::Customer,
            'is_vip' => true,
            'abo_no' => fake()->numerify('ABO-######'),
        ]);
    }

    /**
     * A lead — name and phone only, not yet a buyer.
     */
    public function lead(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => CustomerCategory::Lead,
            'kp_no' => null,
            'email' => null,
            'address' => null,
        ]);
    }

    public function prospect(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => CustomerCategory::Prospect,
        ]);
    }

    /**
     * Attach the customer to a specific Business Owner (partner_id follows).
     */
    public function forBusinessOwner(BusinessOwner $businessOwner): static
    {
        return $this->state(fn (array $attributes): array => [
            'business_owner_id' => $businessOwner->id,
            'partner_id' => $businessOwner->partner_id,
        ]);
    }

    /**
     * A Partner-owned customer with no Business Owner (legacy Partner-led flow).
     */
    public function forPartner(Partner $partner): static
    {
        return $this->state(fn (array $attributes): array => [
            'business_owner_id' => null,
            'partner_id' => $partner->id,
        ]);
    }
}
