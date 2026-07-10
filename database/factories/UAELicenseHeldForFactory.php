<?php

namespace Database\Factories;

use App\Models\UAELicenseHeldFor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UAELicenseHeldFor>
 */
class UAELicenseHeldForFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'text' => $this->faker->words(3, true),
            'is_active' => 1,
            'is_back_home_license_active' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => 0]);
    }
}
