<?php

namespace Database\Factories;

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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->unique()->lexify('???'),
            'text' => $this->faker->company(),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the insurance provider is RSA.
     */
    public function rsa()
    {
        return $this->state(fn (array $attributes) => [
            'code' => \App\Enums\InsuranceProvidersEnum::RSA,
            'text' => 'RSA Insurance',
        ]);
    }

    /**
     * Indicate that the insurance provider is AXA.
     */
    public function axa()
    {
        return $this->state(fn (array $attributes) => [
            'code' => \App\Enums\InsuranceProvidersEnum::AXA,
            'text' => 'AXA Insurance',
        ]);
    }
}

