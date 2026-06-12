<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Insured;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuredFactory extends Factory
{
    protected $model = Insured::class;

    public function definition(): array
    {
        return [
            'customer_type' => 'Individual',
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ];
    }

    public function tradeLicense(): static
    {
        return $this->state([
            'id_type' => 'tradeLicense',
            'id_number' => $this->faker->numerify('####################'),
            'trade_license_no' => $this->faker->numerify('####################'),
        ]);
    }
}
