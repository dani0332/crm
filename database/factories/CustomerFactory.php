<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'emirates_id_number' => '784-'.random_int(1000, 9999).'-'.random_int(1000000, 9999999).'-1',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile_no' => '+971'.random_int(500000000, 599999999),
            'dob' => fake()->date('Y-m-d', '-25 years'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

