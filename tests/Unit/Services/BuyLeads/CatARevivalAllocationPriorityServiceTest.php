<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Models\BuyLeadConfigurationNationality;
use App\Models\CarQuote;
use App\Services\BuyLeads\CatARevivalAllocationPriorityService;
use Carbon\Carbon;
use Tests\Helpers\TestSchemaCreator;

test('effectiveCarValue prefers positive car_value over car_value_tier', function () {
    $lead = new CarQuote([
        'car_value' => 50000,
        'car_value_tier' => 100000,
    ]);

    expect(CatARevivalAllocationPriorityService::effectiveCarValue($lead))->toBe(50000.0);
});

test('effectiveCarValue uses car_value_tier when car_value is not positive', function () {
    $lead = new CarQuote([
        'car_value' => 0,
        'car_value_tier' => 30000,
    ]);

    expect(CatARevivalAllocationPriorityService::effectiveCarValue($lead))->toBe(30000.0);
});

test('hasHigherPriorityUnassignedLead ignores higher leads outside the lookback window', function () {
    TestSchemaCreator::createMinimalSchema();
    Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00'));

    BuyLeadConfigurationNationality::query()->create([
        'quote_type' => QuoteTypes::CAR_CAT_A,
        'nationality_id' => 99,
    ]);

    CarQuote::factory()->create([
        'source' => LeadSourceEnum::REVIVAL,
        'nationality_id' => 99,
        'car_value' => 500000,
        'advisor_id' => null,
        'quote_status_id' => 1,
        'created_at' => now()->subDays(20),
    ]);

    $low = CarQuote::factory()->create([
        'source' => LeadSourceEnum::REVIVAL,
        'nationality_id' => 99,
        'car_value' => 1000,
        'advisor_id' => null,
        'quote_status_id' => 1,
        'created_at' => now()->subHour(),
    ]);

    expect(CatARevivalAllocationPriorityService::hasHigherPriorityUnassignedLead($low))->toBeFalse();

    Carbon::setTestNow();
});
