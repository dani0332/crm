<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Nationality;
use Illuminate\Database\Eloquent\Factories\Factory;

class NationalityFactory extends Factory
{
    protected $model = Nationality::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'text' => $this->faker->country(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 200),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the nationality is United Arab Emirates.
     */
    public function uae(): static
    {
        return $this->state(fn (array $attributes) => [
            'text' => 'United Arab Emirates',
            'code' => 'UAE',
        ]);
    }

    /**
     * Indicate that the nationality is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
