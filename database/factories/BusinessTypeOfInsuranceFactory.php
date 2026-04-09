<?php

namespace Database\Factories;

use App\Models\BusinessTypeOfInsurance;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessTypeOfInsuranceFactory extends Factory
{
    protected $model = BusinessTypeOfInsurance::class;

    public function definition()
    {
        return [
            'text' => $this->faker->words(3, true),
            'code' => $this->faker->unique()->lexify('???'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
