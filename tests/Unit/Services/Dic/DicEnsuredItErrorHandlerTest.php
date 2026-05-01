<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicEnsuredItErrorHandler;
use Tests\Helpers\DicTestHelper;

it('maps AUTH_ERROR token expired to resolution message', function () {
    $response = DicTestHelper::clientResponse([
        'code' => 'AUTH_ERROR',
        'message' => 'Token Expired',
    ], 401);

    $mapped = DicEnsuredItErrorHandler::map($response);

    expect($mapped['error'])->toBe('AUTH_ERROR: Token Expired')
        ->and($mapped['message'])->toContain('token expired')
        ->and($mapped['message'])->toContain('Generate a new access token');
});

it('maps AUTH_ERROR invalid token to resolution message', function () {
    $response = DicTestHelper::clientResponse([
        'code' => 'AUTH_ERROR',
        'message' => 'Invalid Auth Token Found',
    ], 401);

    $mapped = DicEnsuredItErrorHandler::map($response);

    expect($mapped['error'])->toBe('AUTH_ERROR: Invalid Auth Token Found')
        ->and($mapped['message'])->toContain('invalid auth token');
});

it('maps VALIDATION_ERROR with field message', function () {
    $response = DicTestHelper::clientResponse([
        'code' => 'VALIDATION_ERROR',
        'message' => "'travel_region' is not one of the acceptable values.",
    ], 400);

    $mapped = DicEnsuredItErrorHandler::map($response);

    expect($mapped['error'])->toContain('VALIDATION_ERROR')
        ->and($mapped['message'])->toContain('validation error')
        ->and($mapped['message'])->toContain('travel_region');
});

it('maps INTERNAL_ERROR for policy stores style responses', function () {
    $response = DicTestHelper::clientResponse([
        'code' => 'INTERNAL_ERROR',
        'message' => 'Price not found',
    ], 500);

    $mapped = DicEnsuredItErrorHandler::map($response);

    expect($mapped['error'])->toBe('INTERNAL_ERROR: Price not found')
        ->and($mapped['message'])->toContain('server error')
        ->and($mapped['message'])->toContain('Price not found');
});

it('maps issuance policy already sold (400 with policyStatus SOLD)', function () {
    $response = DicTestHelper::clientResponse([
        'certificateNumber' => 'UNIT-CERT-1',
        'code' => 'VALIDATION_ERROR',
        'message' => 'Policy already sold',
        'policyStatus' => 'SOLD',
    ], 400);

    $mapped = DicEnsuredItErrorHandler::map($response);

    expect($mapped['message'])->toContain('already sold')
        ->and($mapped['message'])->toContain('Verify policy status');
});

it('falls back to HTTP status when body is not JSON', function () {
    $response = DicTestHelper::clientResponse('Gateway timeout upstream', 502);

    $mapped = DicEnsuredItErrorHandler::map($response);

    expect($mapped['error'])->toBe('Gateway timeout upstream')
        ->and($mapped['message'])->toBe('DIC request failed (HTTP 502).');
});

it('uses API message for unknown code with 403', function () {
    $response = DicTestHelper::clientResponse([
        'code' => 'UNKNOWN',
        'message' => 'Forbidden scope',
    ], 403);

    $mapped = DicEnsuredItErrorHandler::map($response);

    expect($mapped['message'])->toContain('HTTP 403')
        ->and($mapped['message'])->toContain('Forbidden scope');
});
