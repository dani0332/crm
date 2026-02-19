<?php

namespace Database\Factories;

use App\Enums\CollectionTypeEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\CarQuote;
use App\Models\Payment;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
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
            'payment_status_id' => PaymentStatusEnum::NEW,
            'payment_methods_code' => PaymentMethodsEnum::InsurerPayment,
            'collection_type' => CollectionTypeEnum::INSURER,
            'frequency' => PaymentFrequency::UPFRONT,
            'total_payments' => 1,
            'discount_value' => 0,
            'collection_date' => now(),
            'captured_amount' => 0,
        ];
    }

    /**
     * Define the model's cyber quote state.
     *
     * @return array
     */
    public function cyberPayment($code, $id)
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
            'paymentable_id' => $id,
            'paymentable_type' => PersonalQuote::class,
        ]);
    }

    /**
     * Create a Payment for tests.
     * Accepts a CarQuote object and extracts plan_id, insurance_provider_id, code, and premium.
     * Gets created_by and updated_by from currently logged in user.
     * Uses model-based insertion so observers will run.
     * Uses the default database connection (SQLite in tests as configured in phpunit.xml).
     *
     * @param  CarQuote  $carQuote  The car quote to create payment for
     * @param  array  $attributes  Additional attributes to override defaults
     */
    public function createForSqlite(CarQuote $carQuote, array $attributes = []): Payment
    {
        $user = Auth::user();

        // Get premium from car quote (default to 1000 if not set)
        $premium = $carQuote->premium ?? 1000;

        // Build payment attributes from car quote
        $paymentAttributes = array_merge($this->definition(), [
            'code' => $carQuote->code,
            'plan_id' => $carQuote->plan_id,
            'insurance_provider_id' => $carQuote->insurance_provider_id,
            'paymentable_id' => $carQuote->id,
            'paymentable_type' => CarQuote::class,
            'total_price' => $premium,
            'total_amount' => $premium,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ], $attributes);

        // Use model-based insertion so observers run
        // Uses default connection (SQLite in tests as configured in phpunit.xml)
        return Payment::create($paymentAttributes);
    }
}
