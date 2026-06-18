<?php

namespace Database\Factories;

use App\Models\Lookup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lookup>
 */
class LookupFactory extends Factory
{
    protected $model = Lookup::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->slug(2),
            'code' => $this->faker->unique()->slug(2),
            'text' => $this->faker->words(2, true),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
