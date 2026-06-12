<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuoteStatusEnum;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\Nationality;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HealthQuoteFactory extends Factory
{
    protected $model = HealthQuote::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'code' => 'HQ-'.strtoupper(Str::random(6)),
            'customer_id' => Customer::factory(),
            'nationality_id' => Nationality::factory(),
            'quote_status_id' => QuoteStatusEnum::PaymentPending,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile_no' => '+971'.random_int(500000000, 599999999),
            'policy_start_date' => now()->addDays(1)->format('Y-m-d'),
            'policy_expiry_date' => now()->addYear()->format('Y-m-d'),
            'insurer_quote_number' => 'INS-QUOTE-'.strtoupper(Str::random(6)),
            'source' => 'web',
            'device' => 'desktop',
            'created_at' => now(),
            'updated_at' => now(),
            'dob' => fake()->date('Y-m-d', '-25 years'),
            'gender' => fake()->randomElement(['M', 'F']),
            'is_quote_locked' => false,
        ];
    }

    public function withSTPCase(): static
    {
        return $this->state(function (array $attributes) {
            return [
                // STP case is determined by other factors in the system
                // This is a placeholder state for test semantics
            ];
        });
    }

    public function withNonSTPCase(): static
    {
        return $this->state(function (array $attributes) {
            return [
                // Non-STP case is determined by other factors in the system
                // This is a placeholder state for test semantics
            ];
        });
    }

    /**
     * Quote is locked (no further edits allowed).
     */
    public function locked(): self
    {
        return $this->state(['is_quote_locked' => true]);
    }

    /**
     * Quote with new revamp fields populated.
     */
    public function withRevampFields(string $insureCode = 'ONLY_MYSELF', string $policyHolderCode = 'ME'): self
    {
        return $this->state([
            'insure_code' => $insureCode,
            'policy_holder_code' => $policyHolderCode,
        ]);
    }

}
