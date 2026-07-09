<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'floor_number' => (string) $this->faker->randomDigitNotNull(),
            'building_name' => $this->faker->company(),
            'street' => $this->faker->streetName(),
        ];
    }
}
