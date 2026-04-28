<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\MotorRevivalEnum;
use App\Models\CarQuote;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

test('isRevivalCommsIntentHighOrMedium returns false when source is not revival', function () {
    $carQuote = TestDataSeeder::createCarQuote(['source' => 'ECOM']);

    DB::connection('sqlite')->table('car_quote_request_detail')->insert([
        'car_quote_request_id' => $carQuote->id,
        'engagement_level' => MotorRevivalEnum::INTENT_HIGH->value,
        'engagement_level_updated_at' => now()->subMinutes(20),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $carQuote->refresh();

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeFalse();
});

test('isRevivalCommsIntentHighOrMedium returns true for revival high intent after cooldown', function () {
    $carQuote = TestDataSeeder::createCarQuote(['source' => LeadSourceEnum::REVIVAL]);

    DB::connection('sqlite')->table('car_quote_request_detail')->insert([
        'car_quote_request_id' => $carQuote->id,
        'engagement_level' => MotorRevivalEnum::INTENT_HIGH->value,
        'engagement_level_updated_at' => now()->subMinutes(20),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $carQuote->refresh();

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeTrue();
});

test('isRevivalCommsIntentHighOrMedium returns false when engagement fields are missing', function () {
    $carQuote = TestDataSeeder::createCarQuote(['source' => LeadSourceEnum::REVIVAL]);

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeFalse();
});

test('carQuoteRequestDetail eager load uses carQuoteRequestDetail relation', function () {
    $carQuote = TestDataSeeder::createCarQuote(['source' => LeadSourceEnum::REVIVAL]);

    DB::connection('sqlite')->table('car_quote_request_detail')->insert([
        'car_quote_request_id' => $carQuote->id,
        'engagement_level' => MotorRevivalEnum::INTENT_HIGH->value,
        'engagement_level_updated_at' => now()->subMinutes(20),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $loaded = CarQuote::on('sqlite')
        ->with(['carQuoteRequestDetail:id,car_quote_request_id,engagement_level,engagement_level_updated_at'])
        ->whereKey($carQuote->id)
        ->first();

    expect($loaded)->not->toBeNull()
        ->and($loaded->carQuoteRequestDetail)->not->toBeNull()
        ->and($loaded->carQuoteRequestDetail->engagement_level)->toBe(MotorRevivalEnum::INTENT_HIGH->value);
});
