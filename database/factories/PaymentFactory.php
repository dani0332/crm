<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition($code = ''): array
    {
        return [
            'uuid' => $this->faker->uuid,
            'code' => $code,
            'price_vat_applicable' => $this->faker->randomFloat(2, 1000, 10000),
            'price_vat_not_applicable' => $this->faker->randomFloat(2, 1000, 10000),
            'discount_value' => $this->faker->randomFloat(2, 100, 500),
            'commission_vat_applicable' => $this->faker->randomFloat(2, 1000, 10000),
            'commission_vat_not_applicable' => $this->faker->randomFloat(2, 1000, 10000),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'updated_at' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Payment $payment) {
            if (! Payment::where('uuid', $personalQuote->uuid)->exists()) {
                $personalQuote->payment()->save(Payment::factory()->create(['code' => $personalQuote->code]));
            }
        });
    }
}
