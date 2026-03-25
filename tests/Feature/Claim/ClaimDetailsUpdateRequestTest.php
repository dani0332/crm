<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Http\Requests\ClaimDetailsUpdateRequest;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

// ── failedValidation claim_uuid context ───────────────────────────────────────

test('failedValidation logs claim_uuid from the bound route model', function () {
    $uuid = 'test-claim-uuid-abcd-1234';
    $claimModel = (object) ['uuid' => $uuid];

    $request = Mockery::mock(ClaimDetailsUpdateRequest::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $request->shouldReceive('route')->with('claim')->andReturn($claimModel);
    $request->shouldReceive('getRedirectUrl')->andReturn('/');

    Log::spy();

    $validator = Validator::make([], ['dummy' => 'required']);

    try {
        (new ReflectionMethod($request, 'failedValidation'))
            ->invoke($request, $validator);
    } catch (ValidationException) {
        // failedValidation always throws; we only care about the log side-effect
    }

    $raw = Context::getHidden('__extra') ?? Context::get('__extra');
    $extra = json_decode($raw, true);

    expect($extra['claim_uuid'])->toBe($uuid);
});

test('failedValidation logs null claim_uuid when no claim is bound to the route', function () {
    $request = Mockery::mock(ClaimDetailsUpdateRequest::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $request->shouldReceive('route')->with('claim')->andReturn(null);
    $request->shouldReceive('getRedirectUrl')->andReturn('/');

    Log::spy();

    $validator = Validator::make([], ['dummy' => 'required']);

    try {
        (new ReflectionMethod($request, 'failedValidation'))
            ->invoke($request, $validator);
    } catch (ValidationException) {
        // Expected
    }

    $raw = Context::getHidden('__extra') ?? Context::get('__extra');
    $extra = json_decode($raw, true);

    expect($extra['claim_uuid'])->toBeNull();
});

test('withValidator uses bound claim quote_type_id for LOB-specific validation', function () {
    $claimModel = (object) [
        'quote_type_id' => QuoteTypes::CAR->id(),
    ];

    $request = Mockery::mock(ClaimDetailsUpdateRequest::class)->makePartial();
    $request->shouldReceive('route')->with('claim')->andReturn($claimModel);

    $input = [
        'quote_type_id' => QuoteTypes::HEALTH->id(),
        'plate_number' => null,
        'car_make' => null,
        'car_model' => null,
        'model_year' => null,
    ];

    $request->shouldReceive('filled')->andReturnUsing(function (string $key) use ($input): bool {
        return array_key_exists($key, $input)
            && $input[$key] !== null
            && $input[$key] !== '';
    });

    $validator = Validator::make([], []);

    $request->withValidator($validator);

    $validator->fails();

    $errors = $validator->errors();

    expect($errors->keys())->toContain('plate_number', 'car_make', 'car_model', 'model_year');
});
