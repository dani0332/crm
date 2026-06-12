<?php

declare(strict_types=1);

// Car revival ILA waits after engagement_level_updated_at: 15 minutes (high intent), 3 hours (medium intent).
// Values are defined on MotorRevivalEnum::ILA_HIGH_INTENT_WAIT_MINUTES and ILA_MEDIUM_INTENT_WAIT_HOURS.

use App\Enums\LeadSourceEnum;
use App\Enums\MotorRevivalEnum;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

test('car revival ILA wait periods are 15 minutes for high intent and 3 hours for medium intent', function () {
    expect(MotorRevivalEnum::ILA_HIGH_INTENT_WAIT_MINUTES)->toBe(15)
        ->and(MotorRevivalEnum::ILA_MEDIUM_INTENT_WAIT_HOURS)->toBe(3);
});

test('isRevivalCommsIntentHighOrMedium returns false when source is not revival', function () {
    $carQuote = CarQuote::factory()->create([
        'source' => 'ECOM',
    ]);

    CarQuoteRequestDetail::factory()
        ->forCarQuote($carQuote)
        ->create([
            'engagement_level' => MotorRevivalEnum::INTENT_HIGH->value,
            'engagement_level_updated_at' => now()->subMinutes(MotorRevivalEnum::ILA_HIGH_INTENT_WAIT_MINUTES + 1),
        ]);

    $carQuote->refresh();

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeFalse();
});

test('isRevivalCommsIntentHighOrMedium returns false for revival high intent before 15 minute ILA wait', function () {
    $carQuote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::REVIVAL,
    ]);

    CarQuoteRequestDetail::factory()
        ->forCarQuote($carQuote)
        ->create([
            'engagement_level' => MotorRevivalEnum::INTENT_HIGH->value,
            'engagement_level_updated_at' => now()->subMinutes(MotorRevivalEnum::ILA_HIGH_INTENT_WAIT_MINUTES - 1),
        ]);

    $carQuote->refresh();

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeFalse();
});

test('isRevivalCommsIntentHighOrMedium returns true for revival high intent after 15 minute ILA wait', function () {
    $carQuote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::REVIVAL,
    ]);

    CarQuoteRequestDetail::factory()
        ->forCarQuote($carQuote)
        ->create([
            'engagement_level' => MotorRevivalEnum::INTENT_HIGH->value,
            'engagement_level_updated_at' => now()->subMinutes(MotorRevivalEnum::ILA_HIGH_INTENT_WAIT_MINUTES + 1),
        ]);

    $carQuote->refresh();

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeTrue();
});

test('isRevivalCommsIntentHighOrMedium returns false when engagement fields are missing', function () {
    $carQuote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::REVIVAL,
    ]);

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeFalse();
});

test('isRevivalCommsIntentHighOrMedium returns false for revival medium intent before 3 hour ILA wait', function () {
    $carQuote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::REVIVAL,
    ]);

    CarQuoteRequestDetail::factory()
        ->forCarQuote($carQuote)
        ->create([
            'engagement_level' => MotorRevivalEnum::MEDIUM_INTENT->value,
            'engagement_level_updated_at' => now()->subHours(MotorRevivalEnum::ILA_MEDIUM_INTENT_WAIT_HOURS - 1),
        ]);

    $carQuote->refresh();

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeFalse();
});

test('isRevivalCommsIntentHighOrMedium returns true for revival medium intent after 3 hour ILA wait', function () {
    $carQuote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::REVIVAL,
    ]);

    CarQuoteRequestDetail::factory()
        ->forCarQuote($carQuote)
        ->create([
            'engagement_level' => MotorRevivalEnum::MEDIUM_INTENT->value,
            'engagement_level_updated_at' => now()->subHours(MotorRevivalEnum::ILA_MEDIUM_INTENT_WAIT_HOURS + 1),
        ]);

    $carQuote->refresh();

    expect($carQuote->isRevivalCommsIntentHighOrMedium())->toBeTrue();
});

test('carQuoteRequestDetail eager load uses carQuoteRequestDetail relation', function () {
    $carQuote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::REVIVAL,
    ]);

    CarQuoteRequestDetail::factory()
        ->forCarQuote($carQuote)
        ->create([
            'engagement_level' => MotorRevivalEnum::INTENT_HIGH->value,
            'engagement_level_updated_at' => now()->subMinutes(MotorRevivalEnum::ILA_HIGH_INTENT_WAIT_MINUTES + 1),
        ]);

    $loaded = CarQuote::query()
        ->with(['carQuoteRequestDetail:id,car_quote_request_id,engagement_level,engagement_level_updated_at'])
        ->whereKey($carQuote->id)
        ->first();

    expect($loaded)->not->toBeNull()
        ->and($loaded->carQuoteRequestDetail)->not->toBeNull()
        ->and($loaded->carQuoteRequestDetail->engagement_level)->toBe(MotorRevivalEnum::INTENT_HIGH->value);
});
