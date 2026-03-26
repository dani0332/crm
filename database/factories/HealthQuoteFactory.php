<?php

namespace Database\Factories;

use App\Models\HealthQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HealthQuote>
 */
class HealthQuoteFactory extends Factory
{
    protected $model = HealthQuote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => 'hq-'.uniqid(),
            'code' => 'HEA-'.strtoupper(uniqid()),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile_no' => '+971501234567',
            'dob' => fake()->date('Y-m-d', '-25 years'),
            'gender' => fake()->randomElement(['M', 'F']),
            'source' => 'IMCRM',
            'is_quote_locked' => false,
            'is_quote_revisable' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Quote is locked (no further edits allowed).
     */
    public function locked(): self
    {
        return $this->state(['is_quote_locked' => true]);
    }

    /**
     * Quote is revisable.
     */
    public function revisable(): self
    {
        return $this->state(['is_quote_revisable' => true]);
    }

    /**
     * Quote with new revamp fields populated.
     */
    public function withRevampFields(string $insureCode = 'ONLY_MYSELF', string $policyHolderCode = 'ME'): self
    {
        return $this->state([
            'insure_code' => $insureCode,
            'policy_holder_code' => $policyHolderCode,
            'is_quote_revisable' => false,
        ]);
    }
}
