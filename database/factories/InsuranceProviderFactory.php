<?php

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InsuranceProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('???'),
            'text' => $this->faker->company(),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}