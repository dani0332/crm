<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypeShortCode;
use App\Models\PersonalQuote;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Collection;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

if (! function_exists('invokeValidatePreviousRefId')) {
    function invokeValidatePreviousRefId(
        object $leadData,
        mixed $quoteTypeObject,
        Collection $errors,
        bool $isQuotePersonal = false,
        ?object $quoteType = null
    ): void {
        static $method = null;

        if ($method === null) {
            $method = new ReflectionMethod(RenewalsUploadService::class, 'validatePreviousRefId');
            $method->setAccessible(true);
        }

        $service = (new ReflectionClass(RenewalsUploadService::class))->newInstanceWithoutConstructor();
        $method->invoke($service, $leadData, $quoteTypeObject, $errors, $isQuotePersonal, $quoteType);
    }
}

if (! function_exists('makeFakeQuoteType')) {
    function makeFakeQuoteType(string $shortCode, int $id): object
    {
        return (object) ['short_code' => $shortCode, 'id' => $id];
    }
}

test('bike with matching same-type BIK ref passes validation', function () {
    PersonalQuote::factory()->create([
        'code' => 'BIK-SAMEREF1',
        'quote_type_id' => QuoteTypeId::Bike,
    ]);

    $errors = collect();
    invokeValidatePreviousRefId(
        (object) ['previous_ref_id' => 'BIK-SAMEREF1'],
        PersonalQuote::query(),
        $errors,
        isQuotePersonal: true,
        quoteType: makeFakeQuoteType(QuoteTypeShortCode::BIK, QuoteTypeId::Bike),
    );

    expect($errors)->toBeEmpty();
});

test('bike with matching CAR ref passes validation because bike-to-car is the only allowed cross-type', function () {
    PersonalQuote::factory()->create([
        'code' => 'CAR-CROSSREF1',
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    $errors = collect();
    invokeValidatePreviousRefId(
        (object) ['previous_ref_id' => 'CAR-CROSSREF1'],
        PersonalQuote::query(),
        $errors,
        isQuotePersonal: true,
        quoteType: makeFakeQuoteType(QuoteTypeShortCode::BIK, QuoteTypeId::Bike),
    );

    expect($errors)->toBeEmpty();
});

test('home upload with bike prev_ref_id fails validation', function () {
    PersonalQuote::factory()->create([
        'code' => 'BIK-WRONGREF1',
        'quote_type_id' => QuoteTypeId::Bike,
    ]);

    $errors = collect();
    invokeValidatePreviousRefId(
        (object) ['previous_ref_id' => 'BIK-WRONGREF1'],
        PersonalQuote::query(),
        $errors,
        isQuotePersonal: true,
        quoteType: makeFakeQuoteType(QuoteTypeShortCode::HOM, QuoteTypeId::Home),
    );

    expect($errors)->toHaveCount(1)
        ->and($errors->first())->toContain('BIK-WRONGREF1');
});

test('car upload with bike prev_ref_id fails validation', function () {
    PersonalQuote::factory()->create([
        'code' => 'BIK-WRONGREF2',
        'quote_type_id' => QuoteTypeId::Bike,
    ]);

    $errors = collect();
    invokeValidatePreviousRefId(
        (object) ['previous_ref_id' => 'BIK-WRONGREF2'],
        PersonalQuote::query(),
        $errors,
        isQuotePersonal: true,
        quoteType: makeFakeQuoteType(QuoteTypeShortCode::CAR, QuoteTypeId::Car),
    );

    expect($errors)->toHaveCount(1)
        ->and($errors->first())->toContain('BIK-WRONGREF2');
});

test('bike with CAR ref that does not exist in personal_quotes fails validation', function () {
    $errors = collect();
    invokeValidatePreviousRefId(
        (object) ['previous_ref_id' => 'CAR-NONEXISTENT'],
        PersonalQuote::query(),
        $errors,
        isQuotePersonal: true,
        quoteType: makeFakeQuoteType(QuoteTypeShortCode::BIK, QuoteTypeId::Bike),
    );

    expect($errors)->toHaveCount(1)
        ->and($errors->first())->toContain('CAR-NONEXISTENT');
});

test('skips validation when previous_ref_id is empty', function () {
    $errors = collect();
    invokeValidatePreviousRefId(
        (object) ['previous_ref_id' => null],
        PersonalQuote::query(),
        $errors,
        isQuotePersonal: true,
        quoteType: makeFakeQuoteType(QuoteTypeShortCode::BIK, QuoteTypeId::Bike),
    );

    expect($errors)->toBeEmpty();
});

test('skips validation when quoteTypeObject is false', function () {
    $errors = collect();
    invokeValidatePreviousRefId(
        (object) ['previous_ref_id' => 'BIK-ANYCODE'],
        false,
        $errors,
    );

    expect($errors)->toBeEmpty();
});
