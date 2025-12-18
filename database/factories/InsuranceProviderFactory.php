<?php

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InsuranceProvider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = [
            ['code' => 'AXA', 'text' => 'Gulf Insurance'],
            ['code' => 'QIC', 'text' => 'Qatar Insurance'],
            ['code' => 'RAK', 'text' => 'RAK Insurance'],
            ['code' => 'TM', 'text' => 'Tokio Marine']
        ];

        $provider = $this->faker->randomElement($providers);

        return [
            'code' => $provider['code'],
            'text' => $provider['text'],
            'is_active' => 1,
        ];
    }

    /**
     * Indicate that the provider should be inactive.
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
}

