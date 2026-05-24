<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BusinessQuote;
use App\Models\CustomerMembers;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerMembersFactory extends Factory
{
    protected $model = CustomerMembers::class;

    public function definition(): array
    {
        return [
            'quote_type' => BusinessQuote::class,
            'quote_id' => $this->faker->randomNumber(5),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ];
    }
}
