<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GenericRequestEnum;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAdditionalContact>
 */
class CustomerAdditionalContactFactory extends Factory
{
    protected $model = CustomerAdditionalContact::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (CustomerAdditionalContact $contact) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $contact->setConnection('sqlite');
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
            'customer_id' => Customer::factory(),
            'key' => GenericRequestEnum::EMAIL,
            'value' => $this->faker->unique()->safeEmail(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the contact is an email.
     */
    public function email()
    {
        return $this->state(fn (array $attributes) => [
            'key' => GenericRequestEnum::EMAIL,
        ]);
    }

    /**
     * Indicate that the contact is a mobile number.
     */
    public function mobile()
    {
        return $this->state(fn (array $attributes) => [
            'key' => GenericRequestEnum::MOBILE_NO,
            'value' => '+971'.$this->faker->numerify('#########'),
        ]);
    }

    /**
     * Indicate that the contact belongs to a specific customer.
     */
    public function forCustomer(int|Customer $customer)
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
        ]);
    }

    /**
     * Indicate that the contact has a specific value.
     */
    public function withValue(string $value)
    {
        return $this->state(fn (array $attributes) => [
            'value' => $value,
        ]);
    }
}
