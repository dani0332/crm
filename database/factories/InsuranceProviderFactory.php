<?php

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceProviderFactory extends Factory
{
    protected $model = InsuranceProvider::class;

    public function definition()
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

