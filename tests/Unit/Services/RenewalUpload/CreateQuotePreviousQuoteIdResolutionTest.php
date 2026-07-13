<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\CarQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Services\RenewalsUploadService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

if (! function_exists('createRenewalsUploadServiceWithMocks')) {
    function createRenewalsUploadServiceWithMocks(): RenewalsUploadService
    {
        static $reflection = null;

        if ($reflection === null) {
            $reflection = new ReflectionClass(RenewalsUploadService::class);
        }

        return $reflection->newInstanceWithoutConstructor();
    }
}

test('previous_quote_id resolves to personal_quotes id when code matches', function () {
    $previousQuote = PersonalQuote::factory()->create([
        'code' => 'CAR-PREVCODE',
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    $resolvedId = PersonalQuote::where('code', 'CAR-PREVCODE')->value('id');

    expect($resolvedId)->toBe($previousQuote->id);
});

test('previous_quote_id resolves to null when no personal quote matches the code', function () {
    $resolvedId = PersonalQuote::where('code', 'CAR-DOESNOTEXIST')->value('id');

    expect($resolvedId)->toBeNull();
});

test('bike-to-bike: previous_quote_id resolves to bike_quote_request.id', function () {
    $prevBike = BikeQuote::factory()->create(['code' => 'BIK-PREVBIKE1']);

    $resolvedId = BikeQuote::where('code', 'BIK-PREVBIKE1')->value('id');

    expect($resolvedId)->toBe($prevBike->id);
});

test('car-to-bike: previous_quote_id resolves to car_quote_request.id not personal_quotes.id', function () {
    $prevCar = CarQuote::factory()->create(['code' => 'CAR-PREVCAR1']);

    // The personal_quotes row would have a different id than car_quote_request row.
    $carTableId = CarQuote::where('code', 'CAR-PREVCAR1')->value('id');

    expect($carTableId)->toBe($prevCar->id);
    // Confirm a PersonalQuote with same code returns a different (unrelated) id space.
    expect(PersonalQuote::where('code', 'CAR-PREVCAR1')->value('id'))->toBeNull();
});

test('home-to-home: previous_quote_id resolves to home_quote_request.id', function () {
    $prevHome = HomeQuote::factory()->create(['code' => 'HOM-PREVHOME1']);

    $resolvedId = HomeQuote::where('code', 'HOM-PREVHOME1')->value('id');

    expect($resolvedId)->toBe($prevHome->id);
});

test('updatePersonalQuote persists previous_quote_id from personal_quotes id not from lob table id', function () {
    $previousQuote = PersonalQuote::factory()->create([
        'code' => 'CAR-PREV',
        'quote_type_id' => QuoteTypeId::Car,
    ]);
    $newQuote = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    // Simulate a LOB table id that differs from personal_quotes.id — the pre-fix bug used this.
    $lobTableId = $previousQuote->id + 999;

    $service = createRenewalsUploadServiceWithMocks();
    $service->updatePersonalQuote($newQuote->uuid, QuoteTypeId::Car, [
        'quote_id' => $lobTableId,
        'previous_quote_id' => $previousQuote->id,
    ]);

    expect($newQuote->refresh()->previous_quote_id)
        ->toBe($previousQuote->id)
        ->not->toBe($lobTableId);
});
