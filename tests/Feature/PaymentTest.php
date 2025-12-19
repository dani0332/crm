<?php

use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
    
    // Create factories using SQLite connection
    // Since models have hardcoded MySQL connection, we use factories to generate
    // attributes but insert via DB facade with SQLite connection
    
    $db = DB::connection('sqlite');
    
    // Create InsuranceProvider using factory definition directly
    $providerFactory = InsuranceProvider::factory();
    $providerAttributes = $providerFactory->definition();
    $providerId = $db->table('insurance_provider')->insertGetId(array_merge($providerAttributes, [
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    $this->insuranceProvider = InsuranceProvider::on('sqlite')->find($providerId);
    
    // Create CarPlan
    $planFactory = CarPlan::factory();
    $planAttributes = array_merge($planFactory->definition(), [
        'provider_id' => $providerId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $planId = $db->table('car_plan')->insertGetId($planAttributes);
    $this->carPlan = CarPlan::on('sqlite')->find($planId);
    
    // Generate UUID in format CAR-ABCDEF12345
    $this->quoteCode = 'CAR-ABCDEF12345';
    $this->quoteUuid = 'ABCDEF12345'; // Extract UUID part (without CAR- prefix)
    
    // Create CarQuote using factory method that handles SQLite connection
    $this->carQuote = CarQuote::factory()->createForSqlite([
        'uuid' => $this->quoteUuid,
        'code' => $this->quoteCode,
        'insurance_provider_id' => $providerId,
        'plan_id' => $planId,
    ]);
});

test('payment should be created', function () {
    // Create payment using factory with CarQuote
    $payment = \App\Models\Payment::factory()->createForSqlite($this->carQuote);
    
    // Refresh payment to get latest values from database (including observer updates)
    $payment->refresh();
    
    // Create payment split using factory with Payment
    $paymentSplit = \App\Models\PaymentSplits::factory()->createForSqlite($payment);
    
    // Refresh payment split to get latest values from database (including observer updates)
    $paymentSplit->refresh();
    
    // Log complete payment object for verification
    \Illuminate\Support\Facades\Log::info('Payment Object:', [
        'payment' => $payment->toArray(),
        'payment_attributes' => $payment->getAttributes(),
    ]);
    
    // Log complete car quote object for verification
    \Illuminate\Support\Facades\Log::info('Car Quote Object:', [
        'car_quote' => $this->carQuote->toArray(),
        'car_quote_attributes' => $this->carQuote->getAttributes(),
    ]);
    
    // Log payment split object for verification (using getAttributes to avoid relationship queries)
    \Illuminate\Support\Facades\Log::info('Payment Split Object:', [
        'payment_split_attributes' => $paymentSplit->getAttributes(),
    ]);
    
    // Validate payment
    expect($payment->code)->toBe($this->quoteCode)
        ->and($payment->plan_id)->toBe($this->carPlan->id)
        ->and($payment->insurance_provider_id)->toBe($this->insuranceProvider->id)
        ->and($payment->total_price)->toBe($this->carQuote->premium)
        ->and($payment->total_amount)->toBe($this->carQuote->premium)
        ->and($payment->discount_value)->toBe(0)
        ->and($payment->created_by)->toBe($this->user->id)
        ->and($payment->updated_by)->toBe($this->user->id)
        ->and($payment->paymentable_id)->toBe($this->carQuote->id)
        ->and($payment->paymentable_type)->toBe(\App\Models\CarQuote::class);
    
    // Validate payment split
    expect($paymentSplit->code)->toBe($payment->code)
        ->and($paymentSplit->payment_amount)->toBe($payment->total_price)
        ->and($paymentSplit->discount_value)->toBe(0)
        ->and($paymentSplit->sr_no)->toBe(1)
        ->and($paymentSplit->payment_method)->toBe(\App\Enums\PaymentMethodsEnum::InsurerPayment)
        ->and($paymentSplit->payment_status_id)->toBe(\App\Enums\PaymentStatusEnum::NEW);
    
    
    // Assert that observers ran (VAT fields should be set, not null)
    expect($payment->price_vat_applicable)->not->toBeNull()
        ->and($payment->price_vat)->not->toBeNull()
        ->and($paymentSplit->price_vat_applicable)->not->toBeNull()
        ->and($paymentSplit->price_vat)->not->toBeNull();
   
});
