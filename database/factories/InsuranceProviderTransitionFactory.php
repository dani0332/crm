<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsuranceProviderTransition>
 */
class InsuranceProviderTransitionFactory extends Factory
{
    protected $model = InsuranceProviderTransition::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (InsuranceProviderTransition $transition) {
            if (app()->environment('testing')) {
                $transition->setConnection('sqlite');
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
            'source_insurance_provider_id' => InsuranceProvider::factory(),
            'target_insurance_provider_id' => InsuranceProvider::factory(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Set the source and target providers explicitly.
     */
    public function between(int $sourceProviderId, int $targetProviderId): static
    {
        return $this->state(fn (array $attributes) => [
            'source_insurance_provider_id' => $sourceProviderId,
            'target_insurance_provider_id' => $targetProviderId,
        ]);
    }
}
