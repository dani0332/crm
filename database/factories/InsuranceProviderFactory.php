<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InsuranceProvidersEnum;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceProviderFactory extends Factory
{
    protected $model = InsuranceProvider::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (InsuranceProvider $insuranceProvider) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $insuranceProvider->setConnection('sqlite');
            }
        });
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('???'),
            'text' => $this->faker->company().' Insurance',
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function adnic(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'code' => InsuranceProvidersEnum::ADNIC,
                'text' => 'ADNIC Insurance',
            ];
        });
    }
    /**
     * Indicate that the insurance provider is RSA.
     */
    public function rsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => InsuranceProvidersEnum::RSA,
            'text' => 'RSA Insurance',
        ]);
    }
    /**
     * Indicate that the insurance provider is AXA.
     */
    public function axa(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => InsuranceProvidersEnum::AXA,
            'text' => 'AXA Insurance',
        ]);
    }

    /**
     * Indicate that the insurance provider is TM (Phoenix).
     */
    public function tm(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => InsuranceProvidersEnum::TM,
            'text' => 'TM Insurance',
        ]);
    }
}
