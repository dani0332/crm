<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\HealthQuote;
use App\Models\InsuranceProvider;
use App\Models\PolicyIssuance;
use Illuminate\Database\Eloquent\Factories\Factory;

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
