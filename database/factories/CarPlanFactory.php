<?php

namespace Database\Factories;

use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarPlanFactory extends Factory
{
    protected $model = CarPlan::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (CarPlan $carPlan) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $carPlan->setConnection('sqlite');
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
            'provider_id' => InsuranceProvider::factory(),
            'plan_name' => $this->faker->words(3, true),
            'is_active' => 1,
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{5,10}'),
            'text' => $this->faker->words(2, true),
            'repair_type' => $this->faker->randomElement(['TPL', 'COMP']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the car plan belongs to a specific insurance provider.
     */
    public function forInsuranceProvider($insuranceProviderId)
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $insuranceProviderId,
        ]);
    }
}
