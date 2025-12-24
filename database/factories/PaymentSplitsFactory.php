<?php

namespace Database\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentSplits>
 */
class PaymentSplitsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sr_no' => 1,
            'payment_method' => PaymentMethodsEnum::InsurerPayment,
            'due_date' => now(),
            'payment_status_id' => PaymentStatusEnum::NEW,
            'discount_value' => 0,
        ];
    }

    /**
     * Create a PaymentSplit for tests.
     * Accepts a Payment object and extracts code and payment_amount from total_price.
     * Uses model-based insertion so observers will run.
     * Uses the default database connection (SQLite in tests as configured in phpunit.xml).
     *
     * @param  Payment  $payment  The payment to create split for
     * @param  array  $attributes  Additional attributes to override defaults
     */
    public function createForSqlite(Payment $payment, array $attributes = []): PaymentSplits
    {
        // Build split attributes from payment
        $splitAttributes = array_merge($this->definition(), [
            'code' => $payment->code,
            'payment_amount' => $payment->total_price,
            'insurer_receipt_number' => $payment->code,
        ], $attributes);

        // Use model-based insertion so observers run
        // Uses default connection (SQLite in tests as configured in phpunit.xml)
        return PaymentSplits::create($splitAttributes);
    }
}
