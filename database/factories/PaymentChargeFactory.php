<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PaymentCharge;
use App\Models\PaymentSplit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentChargeFactory extends Factory
{
    protected $model = PaymentCharge::class;

    public function definition(): array
    {
        return [
            'payment_splits_id' => PaymentSplit::factory(),
            'transaction_id' => 'ch_test_'.uniqid(),
            'amount' => fake()->randomFloat(2, 500, 5000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
