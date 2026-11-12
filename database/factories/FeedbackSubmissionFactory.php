<?php

namespace Database\Factories;

use App\Models\FeedbackSubmission;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackSubmission>
 */
class FeedbackSubmissionFactory extends Factory
{
    protected $model = FeedbackSubmission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => null,
            'partner_id' => null,
            'full_name' => fake()->name(),
            'hp_no' => '601'.fake()->numerify('#########'),
            'partner_id_input' => null,
            'q1_income_growth' => fake()->boolean(),
            'q2_seeking_way' => fake()->boolean(),
            'q3_interested_tools' => fake()->boolean(),
            'remarks' => fake()->optional()->sentence(),
            'assigned_to_owner' => true,
        ];
    }

    public function forPartner(Partner $partner): static
    {
        return $this->state([
            'partner_id' => $partner->id,
            'partner_id_input' => $partner->partner_no,
            'assigned_to_owner' => false,
        ]);
    }

    public function assignedToOwner(): static
    {
        return $this->state([
            'partner_id' => null,
            'assigned_to_owner' => true,
        ]);
    }
}
