<?php

namespace Database\Factories;

use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CarPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // When creating a CarPlan via the factory, Laravel automatically creates an InsuranceProvider record and uses its id for provider_id.
        return [
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{5,10}'),
            'text' => $this->faker->words(2, true),
            'is_active' => 1,
            'provider_id' => InsuranceProvider::factory(),
        ];
    }

    /**
     * Indicate that the plan should be inactive.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => 0,
            ];
        });
    }

    /**
     * Associate the plan with a specific insurance provider.
     *
     * @param  int|InsuranceProvider  $provider
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forProvider($provider)
    {
        return $this->state(function (array $attributes) use ($provider) {
            return [
                'provider_id' => $provider instanceof InsuranceProvider ? $provider->id : $provider,
            ];
        });
    }
}
