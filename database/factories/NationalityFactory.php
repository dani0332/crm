<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Nationality;
use Illuminate\Database\Eloquent\Factories\Factory;

class NationalityFactory extends Factory
{
    protected $model = Nationality::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->countryCode()),
            'text' => fake()->country(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 200),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

