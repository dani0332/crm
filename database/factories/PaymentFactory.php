<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'code' => 'PAY-'.Str::upper(Str::random(6)),
            'price_vat_applicable' => $this->faker->randomFloat(2, 1000, 5000),
            'price_vat' => 0,
            'commission_vat_applicable' => 0,
            'commission' => 0,
            'commission_vat' => 0,
            'commmission_percentage' => 0,
            'insurer_tax_number' => null,
            'insurer_invoice_date' => now()->toDateString(),
            'insurer_commmission_invoice_number' => null,
        ];
    }

    /**
     * Define the model's cyber quote state.
     *
     * @return array
     */
    public function cyberPayment($code, $id)
    {
        return $this->state(fn(array $attributes) => [
            'code' => $code,
            'paymentable_id' => $id,
            'paymentable_type' => PersonalQuote::class,
        ]);
    }
}
