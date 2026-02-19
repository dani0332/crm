<?php

namespace Database\Factories;

use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyIssuanceFactory extends Factory
{
    protected $model = PolicyIssuance::class;

    public function definition(): array
    {
        return [
            'insurance_provider_id' => null,
            'model_type' => PersonalQuote::class,
            'model_id' => PersonalQuote::factory(),
            'quote_type' => QuoteTypes::CYBER->value,
            'status' => null,
            'completed_step' => null,
            'message' => null,
        ];
    }

    public function forQuote(PersonalQuote $quote): static
    {
        return $this->state(fn () => [
            'model_type' => $quote::class,
            'model_id' => $quote->getKey(),
        ]);
    }
}
