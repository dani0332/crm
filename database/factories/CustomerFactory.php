<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (Customer $customer) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $customer->setConnection('sqlite');
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => '+971'.$this->faker->numerify('#########'),
            'dob' => $this->faker->date('Y-m-d', '-25 years'),
            'emirates_id_number' => '784-'.$this->faker->numerify('####').'-'.$this->faker->numerify('#######').'-'.$this->faker->randomDigit(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the customer has a specific email.
     */
    public function withEmail(string $email)
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Indicate that the customer has a specific customer ID.
     */
    public function withCustomerId(int $customerId)
    {
        return $this->state(fn (array $attributes) => [
            'id' => $customerId,
        ]);
    }
}
