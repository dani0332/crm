<?php

namespace Database\Factories;

use App\Models\VisaCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCategory>
 */
class VisaCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('VC_???')),
            'text' => $this->faker->words(3, true),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
            'health_cover_for_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
