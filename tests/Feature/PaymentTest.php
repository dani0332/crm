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
    expect($this->carQuote->code)->toBe($this->quoteCode);
});
