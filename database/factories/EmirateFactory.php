<?php

namespace Database\Factories;

use App\Models\Emirate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmirateFactory extends Factory
{
    protected $model = Emirate::class;

    public function definition(): array
    {
        return [
            'text' => $this->faker->unique()->city,
            'is_active' => true,
        ];
    }
}

