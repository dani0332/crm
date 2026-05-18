<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\HealthQuote;
use App\Models\InsuranceProvider;
use App\Models\PolicyIssuance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class PolicyIssuanceFactory extends Factory
{
    protected $model = PolicyIssuance::class;

    public function definition(): array
    {
        return [
            'model_type' => HealthQuote::class,
            'model_id' => HealthQuote::factory(),
            'insurance_provider_id' => InsuranceProvider::factory(),
            'quote_type' => QuoteTypes::HEALTH->value,
            'status' => PolicyIssuanceEnum::PENDING_STATUS,
            'completed_step' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Associate the policy issuance process with an existing quote (morph target + quote_type).
     */
    public function forQuote(Model $quote): static
    {
        return $this->state(function (array $attributes) use ($quote) {
            $quoteTypeEnum = QuoteTypes::getName($quote->quote_type_id ?? null);

            return [
                'model_type' => $quote->getMorphClass(),
                'model_id' => $quote->getKey(),
                'quote_type' => $quoteTypeEnum?->value ?? QuoteTypes::HEALTH->value,
            ];
        });
    }

    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PolicyIssuanceEnum::PENDING_STATUS,
                'completed_step' => null,
            ];
        });
    }

    public function processing(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PolicyIssuanceEnum::PROCESSING_STATUS,
            ];
        });
    }

    public function timeout(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
            ];
        });
    }
}
