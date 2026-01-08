<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InsuranceProviderFactory extends Factory
{
    protected $model = InsuranceProvider::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(2)),
            'text' => fake()->company().' Insurance',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function adnic(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'code' => 'TE',
                'text' => 'ADNIC Insurance',
            ];
        });
    }
}

