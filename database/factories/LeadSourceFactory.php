<?php

namespace Database\Factories;

use App\Models\LeadSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadSource>
 */
class LeadSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'code' => fake()->optional()->regexify('[A-Z]{3,6}'),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'is_applicable_for_rules' => fake()->boolean(50),
        ];
    }
}
