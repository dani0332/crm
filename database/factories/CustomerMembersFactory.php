<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerMembers>
 */
class CustomerMembersFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->numberBetween(1, 1000),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'dob' => $this->faker->date('Y-m-d', '-20 years'),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'nationality_id' => 1,
            'emirate_of_your_visa_id' => 1,
            'salary_band_id' => 1,
            'member_category_id' => 1,
            'visa_category_id' => null,
            'relation_code' => null,
            'marital_status_id' => null,
            'is_insured' => 1,
            'is_policy_holder' => 0,
            'is_principal' => 0,
            'pec' => 0,
            'is_pec_marked' => 0,
        ];
    }

    /** Principal member — no relation, is_principal = 1. */
    public function principal(): static
    {
        return $this->state([
            'id' => 'temp-'.uniqid(),
            'relation_code' => null,
            'is_principal' => 1,
            'is_policy_holder' => 0,
        ]);
    }

    /** Existing persisted member — numeric id. */
    public function persisted(int $id): static
    {
        return $this->state(['id' => $id]);
    }

    /** Temp (unsaved) member — id prefixed with "temp-". */
    public function temp(): static
    {
        return $this->state(['id' => 'temp-'.uniqid()]);
    }

    /** Policy holder member. */
    public function policyHolder(): static
    {
        return $this->state(['is_policy_holder' => 1]);
    }

    /** Member with pec marked. */
    public function pecMarked(): static
    {
        return $this->state(['is_pec_marked' => 1, 'pec' => 1]);
    }
}
