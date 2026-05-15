<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyIssuanceEnum;
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
        $factory = new self;
        $defaults = $factory->definition();

        // Add common test defaults
        $defaults['id'] = $overrides['id'] ?? 1;
        $defaults['policyIssuance'] = $overrides['policyIssuance'] ?? null;
        $defaults['insurer_api_status'] = $overrides['insurer_api_status'] ?? null;
        $defaults['policy_number'] = $overrides['policy_number'] ?? null;

        return (object) array_merge($defaults, $overrides);
    }

    /**
     * Create a lightweight mock quote object with customer relationship for unit tests.
     * Optimized for memory usage by using plain objects and minimal mocking.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function makeMockWithRelations(array $overrides = []): object
    {
        $quote = self::makeMock($overrides);

        // Use plain object instead of Mockery mock for better memory efficiency
        $mock = new class($quote)
        {
            public function __construct($quote)
            {
                // Copy all properties from quote object
                foreach ((array) $quote as $key => $value) {
                    $this->{$key} = $value;
                }

                // Set up relationships as plain objects (not mocks)
                $this->customer = (object) [
                    'id' => 1,
                    'emirates_id_number' => '784-1234-12345678-1', // Static data for consistency
                    'emirates_id_expiry_date' => '2026-01-15',
                    'dob' => '1990-01-15',
                    'first_name' => $quote->first_name ?? 'John',
                    'last_name' => $quote->last_name ?? 'Doe',
                    'email' => $quote->email ?? 'john.doe@example.com',
                    'mobile_no' => $quote->mobile_no ?? '+971501234567',
                ];

                $this->deviceQuote = (object) [
                    'id' => 1,
                    'imei' => $quote->imei ?? '123456789012345',
                ];

                $this->latestInsured = (object) [
                    'id' => 1,
                    'id_type' => 'emiratesId',
                    'id_number' => '784-1234-12345678-1', // Static data for consistency
                    'first_name' => $quote->first_name ?? 'John',
                    'last_name' => $quote->last_name ?? 'Doe',
                    'email' => $quote->email ?? 'john.doe@example.com',
                    'mobile_no' => $quote->mobile_no ?? '+971501234567',
                ];

                $this->latestPayment = new class
                {
                    public $id = 1;
                    public $code = 'PAY-123456'; // Static code for consistency
                    public $total_amount = 500.00;
                    public $payment_status_id = 1;
                    public $is_main_lead_payment = true;

                    public function paymentSplits()
                    {
                        // Return a mock query builder that can do where() and first()
                        return new class
                        {
                            public function where($column, $value)
                            {
                                return new class($value)
                                {
                                    private $expectedValue;

                                    public function __construct($expectedValue)
                                    {
                                        $this->expectedValue = $expectedValue;
                                    }

                                    public function first()
                                    {
                                        // Return null for credit card payment splits in tests
                                        // (simulating no credit card split exists)
                                        return null;
                                    }
                                };
                            }
                        };
                    }
                };

                // Set up payments relationship as plain object (not mock)
                $this->paymentsRelation = (object) [
                    'mainLeadPayment' => function () {
                        return (object) ['first' => $this->latestPayment];
                    },
                ];
            }

            public function payments()
            {
                return new class($this->latestPayment)
                {
                    private $payment;

                    public function __construct($payment)
                    {
                        $this->payment = $payment;
                    }

                    public function mainLeadPayment()
                    {
                        return $this; // Return self to allow method chaining
                    }

                    public function first()
                    {
                        return $this->payment;
                    }
                };
            }

            public function save()
            {
                return true;
            }

            public function update()
            {
                return true;
            }

            public function getAttribute($attribute)
            {
                return $this->{$attribute} ?? null;
            }
        };

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
            'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
            'completed_step' => null,
        ];

        return (object) array_merge($defaults, $overrides);
    }
}
