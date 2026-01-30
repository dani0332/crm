<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Nationality;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => $this->faker->numerify('05########'),
            'dob' => $this->faker->date('Y-m-d', '-25 years'),
            'nationality_id' => Nationality::factory(),
        ];
    }
}

