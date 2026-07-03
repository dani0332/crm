<?php

namespace Database\Factories;

use App\Enums\AmlAutomationStatus;
use App\Models\AmlAutomation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AmlAutomation>
 */
class AmlAutomationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('TRV-####??'),
            'status' => AmlAutomationStatus::Queue->value,
            'result' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => AmlAutomationStatus::Failed->value,
        ]);
    }
}
