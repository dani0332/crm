<?php

namespace Database\Factories;

use App\Enums\CarRegistrationType;
use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarQuoteFactory extends Factory
{
    protected $model = CarQuote::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (CarQuote $carQuote) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $carQuote->setConnection('sqlite');
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
            'registration_type' => CarRegistrationType::PERSONAL,
            'policy_issuance_automation_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the quote has policy issuance automation enabled.
     */
    public function withAutomationEnabled()
    {
        return $this->state(fn (array $attributes) => [
            'policy_issuance_automation_enabled' => true,
        ]);
    }

    /**
     * Indicate that the quote has policy issuance automation disabled.
     */
    public function withAutomationDisabled()
    {
        return $this->state(fn (array $attributes) => [
            'policy_issuance_automation_enabled' => false,
        ]);
    }

    /**
     * Indicate that the quote belongs to a specific car plan.
     */
    public function forCarPlan($carPlanId)
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $carPlanId,
        ]);
    }

    /**
     * Indicate that the quote has no car plan.
     */
    public function withoutCarPlan()
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => null,
        ]);
    }

    /**
     * Indicate that the quote belongs to a specific customer.
     */
    public function forCustomer(int $customerId)
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Indicate that the quote has a specific email.
     */
    public function withEmail(string $email)
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }
}
