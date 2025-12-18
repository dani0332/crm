<?php

namespace Database\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentSplitsFactory extends Factory
{
    protected $model = PaymentSplits::class;

    public function definition(): array
    {
        return [
            'code' => 'PAY-'.Str::upper(Str::random(6)),
            'payment_method' => PaymentMethodsEnum::CreditCard,
            'reference' => 'SPLIT-'.Str::upper(Str::random(4)),
            'price_vat_applicable' => 0,
            'price_vat' => 0,
        ];
    }

    public function forPayment(Payment $payment): static
    {
        return $this->state([
            'code' => $payment->code,
        ]);
    }

    public function cyberPaymentSplit($code, $id): static
    {
        return $this->state(fn(array $attributes) => [
            'code' => $code,
        ]);
    }
}

