<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\PaymentSplit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentSplitFactory extends Factory
{
    protected $model = PaymentSplit::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'payment_method' => PaymentMethodsEnum::CreditCard,
            'payment_status_id' => PaymentStatusEnum::AUTHORISED,
            'payment_amount' => fake()->randomFloat(2, 500, 5000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function authorised(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status_id' => PaymentStatusEnum::AUTHORISED,
            ];
        });
    }

    public function captured(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status_id' => PaymentStatusEnum::CAPTURED,
            ];
        });
    }
}
