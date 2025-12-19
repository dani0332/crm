<?php

namespace Database\Factories;

use App\Enums\CollectionTypeEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\CarQuote;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    public function definition(): array
    {
        return [
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
     * Create a Payment using SQLite connection for tests.
     * Accepts a CarQuote object and extracts plan_id, insurance_provider_id, code, and premium.
     * Gets created_by and updated_by from currently logged in user.
     *
     * @param CarQuote $carQuote The car quote to create payment for
     * @param array $attributes Additional attributes to override defaults
     * @return Payment
     */
    public function createForSqlite(CarQuote $carQuote, array $attributes = []): Payment
    {
        $db = DB::connection('sqlite');
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
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
        
        // Insert via SQLite connection
        $paymentId = $db->table('payments')->insertGetId($paymentAttributes);
        
        // Load and return model with SQLite connection
        return Payment::on('sqlite')->find($paymentId);
    }
}

// Simple usage - just pass the CarQuote
// $payment = Payment::factory()->createForSqlite($this->carQuote);

// Or override specific attributes if needed
// $payment = Payment::factory()->createForSqlite($this->carQuote, [
//     'payment_methods_code' => PaymentMethodsEnum::CreditCard,
// ]);
