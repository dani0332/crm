<?php

namespace Database\Factories;

use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarQuoteFactory extends Factory
{
    protected $model = CarQuote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = $this->faker->unique()->uuid();

        return [
            'uuid' => $uuid,
            'code' => 'CQ-TEST-'.strtoupper(substr($uuid, 0, 8)),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => '+971'.$this->faker->numerify('#########'),
            'dob' => $this->faker->date('Y-m-d', '-25 years'),
            'source' => $this->faker->randomElement(['IMCRM']),
            'device' => $this->faker->randomElement(['web']),
            'premium' => 1000,
            'customer_id' => 1,
            'quote_status_id' => null,
            'payment_status_id' => null,
            'advisor_id' => null,
            'created_by_id' => null,
            'updated_by_id' => null,
        ];
    }
}
