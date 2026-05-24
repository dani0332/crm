<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomerInsured;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerInsuredFactory extends Factory
{
    protected $model = CustomerInsured::class;

    public function definition(): array
    {
        return [
            'insured_id' => $this->faker->randomNumber(5),
            'customer_id' => $this->faker->randomNumber(5),
            'is_active' => true,
        ];
    }
}
