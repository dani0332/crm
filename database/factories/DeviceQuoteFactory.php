<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuoteTypes;
use App\Models\DeviceQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for DeviceQuote model.
 *
 * @extends Factory<DeviceQuote>
 */
class DeviceQuoteFactory extends Factory
{
    protected $model = DeviceQuote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'DEV-'.$this->faker->unique()->numerify('#####'),
            'uuid' => $this->faker->uuid(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'mobile_no' => '+971'.$this->faker->numerify('5########'),
            'dob' => $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'policy_start_date' => now()->format('Y-m-d'),
            'policy_expiry_date' => now()->addYear()->format('Y-m-d'),
            'insurer_quote_number' => 'NGI-Q-'.$this->faker->unique()->numerify('######'),
            'plan_id' => 1,
            'quote_status_id' => 1,
            'quote_type_id' => QuoteTypes::DEVICE->value,
            'imei' => $this->faker->numerify('###############'),
            'device_make' => $this->faker->randomElement(['Apple', 'Samsung', 'Google', 'OnePlus', 'Huawei']),
            'device_model' => $this->faker->randomElement(['iPhone 15 Pro', 'Galaxy S24', 'Pixel 8', 'OnePlus 12']),
            'device_type' => 'smartphone',
            'device_value' => $this->faker->randomFloat(2, 1000, 10000),
            'device_condition' => $this->faker->randomElement(['new', 'used', 'refurbished']),
            'purchase_date' => now()->subMonths($this->faker->numberBetween(1, 12))->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the quote has a policy number assigned.
     */
    public function withPolicyNumber(?string $policyNumber = null): static
    {
        return $this->state(fn (array $attributes) => [
            'policy_number' => $policyNumber ?? 'NGI-POL-'.$this->faker->unique()->numerify('######'),
        ]);
    }

    /**
     * Indicate that the quote is for NGI insurer.
     */
    public function forNgi(): static
    {
        return $this->state(fn (array $attributes) => [
            'insurance_provider_id' => 1, // NGI provider ID
            'insurer_quote_number' => 'NGI-Q-'.$this->faker->unique()->numerify('######'),
        ]);
    }

    /**
     * Indicate that the quote has failed API status.
     */
    public function withFailedApiStatus(): static
    {
        return $this->state(fn (array $attributes) => [
            'insurer_api_status' => 'FAILED',
        ]);
    }

    /**
     * Indicate that the quote has pending API status.
     */
    public function withPendingApiStatus(): static
    {
        return $this->state(fn (array $attributes) => [
            'insurer_api_status' => 'PENDING',
        ]);
    }

    /**
     * Create a mock quote object without persisting to database.
     * Useful for unit tests that don't need database interaction.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function makeMock(array $overrides = []): object
    {
        $factory = new self();
        $defaults = $factory->definition();

        // Add common test defaults
        $defaults['id'] = $overrides['id'] ?? 1;
        $defaults['policyIssuance'] = $overrides['policyIssuance'] ?? null;
        $defaults['insurer_api_status'] = $overrides['insurer_api_status'] ?? null;
        $defaults['policy_number'] = $overrides['policy_number'] ?? null;

        return (object) array_merge($defaults, $overrides);
    }

    /**
     * Create a mock quote object with customer relationship for unit tests.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function makeMockWithRelations(array $overrides = []): \Mockery\MockInterface
    {
        $quote = self::makeMock($overrides);

        $mock = \Mockery::mock('stdClass');

        // Set all properties from the quote object
        foreach ((array) $quote as $key => $value) {
            $mock->{$key} = $value;
        }

        // Set up common relationships as mocks
        $customerMock = (object) [
            'id' => 1,
            'emirates_id_number' => '784-'.rand(1000, 9999).'-'.rand(1000000, 9999999).'-'.rand(1, 9),
            'emirates_id_expiry_date' => now()->addYears(2)->format('Y-m-d'),
            'dob' => '1990-01-15',
            'first_name' => $quote->first_name ?? 'John',
            'last_name' => $quote->last_name ?? 'Doe',
            'email' => $quote->email ?? 'john.doe@example.com',
            'mobile_no' => $quote->mobile_no ?? '+971501234567',
        ];

        $deviceQuoteMock = (object) [
            'id' => 1,
            'imei' => $quote->imei ?? '123456789012345',
            'device_make' => $quote->device_make ?? 'Apple',
            'device_model' => $quote->device_model ?? 'iPhone 15 Pro',
            'device_type' => $quote->device_type ?? 'smartphone',
            'device_value' => $quote->device_value ?? 5000.00,
        ];

        $insuredMock = (object) [
            'id' => 1,
            'id_type' => 'emiratesId',
            'id_number' => '784-'.rand(1000, 9999).'-'.rand(1000000, 9999999).'-'.rand(1, 9),
            'first_name' => $quote->first_name ?? 'John',
            'last_name' => $quote->last_name ?? 'Doe',
            'email' => $quote->email ?? 'john.doe@example.com',
            'mobile_no' => $quote->mobile_no ?? '+971501234567',
        ];

        // Create payment splits mock
        $paymentSplitsMock = \Mockery::mock();
        $paymentSplitsMock->shouldReceive('where->first')->andReturn(null);

        // Create payment mock with paymentSplits method
        $paymentMock = \Mockery::mock();
        $paymentMock->id = 1;
        $paymentMock->code = 'PAY-'.uniqid();
        $paymentMock->total_amount = 500.00;
        $paymentMock->payment_status_id = 1;
        $paymentMock->is_main_lead_payment = true;
        $paymentMock->shouldReceive('paymentSplits')->andReturn($paymentSplitsMock);

        // Create payments relationship mock
        $paymentsRelationMock = \Mockery::mock();
        $paymentsRelationMock->shouldReceive('mainLeadPayment->first')->andReturn($paymentMock);

        // Setup relationship accessors
        $mock->shouldReceive('getAttribute')->with('customer')->andReturn($customerMock);
        $mock->shouldReceive('getAttribute')->with('deviceQuote')->andReturn($deviceQuoteMock);
        $mock->shouldReceive('getAttribute')->with('latestInsured')->andReturn($insuredMock);
        $mock->shouldReceive('getAttribute')->with('latestPayment')->andReturn($paymentMock);
        $mock->customer = $customerMock;
        $mock->deviceQuote = $deviceQuoteMock;
        $mock->latestInsured = $insuredMock;
        $mock->latestPayment = $paymentMock;

        // Setup method calls for relationships
        $mock->shouldReceive('payments')->andReturn($paymentsRelationMock);
        $mock->shouldReceive('save')->andReturn(true);
        $mock->shouldReceive('update')->andReturn(true);

        return $mock;
    }

    /**
     * Create a mock policy issuance process object for unit tests.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function makeMockProcess(array $overrides = []): object
    {
        $defaults = [
            'id' => 1,
            'status' => \App\Enums\PolicyIssuanceEnum::PROCESSING_STATUS,
            'completed_step' => null,
        ];

        return (object) array_merge($defaults, $overrides);
    }
}
