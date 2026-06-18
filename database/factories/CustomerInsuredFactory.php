<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuoteTypeId;
use App\Models\CustomerInsured;
use App\Models\HealthQuote;
use App\Models\Insured;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerInsured>
 */
class CustomerInsuredFactory extends Factory
{
    protected $model = CustomerInsured::class;

    public function definition(): array
    {
        return [
            'quote_type_id' => QuoteTypeId::Health,
            'quote_request_id' => HealthQuote::factory(),
            'insured_id' => Insured::factory(),
            'customer_id' => fn (array $attributes) => HealthQuote::query()->findOrFail($attributes['quote_request_id'])->customer_id,
            'is_active' => true,
        ];
    }

    /**
     * Link an existing health quote and insured (same customer as the quote).
     */
    public function forActiveHealthLink(HealthQuote $quote, Insured $insured): static
    {
        return $this->state(fn () => [
            'quote_type_id' => QuoteTypeId::Health,
            'quote_request_id' => $quote->id,
            'insured_id' => $insured->id,
            'customer_id' => $quote->customer_id,
            'is_active' => true,
        ]);
    }
}
