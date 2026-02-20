<?php

declare(strict_types=1);

use App\Http\Requests\ClaimDetailsUpdateRequest;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
    } catch (\Illuminate\Validation\ValidationException) {
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
    } catch (\Illuminate\Validation\ValidationException) {
        // Expected
    }

    $raw = Context::getHidden('__extra') ?? Context::get('__extra');
    $extra = json_decode($raw, true);

    expect($extra['claim_uuid'])->toBeNull();
});
