<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Jobs\SendUpdateToCustomerJob;
use Symfony\Component\HttpFoundation\Response;

test('isEmailResponseSuccessful accepts HTTP OK and CREATED for Device quotes', function (int $responseCode, bool $expected) {
    $job = new SendUpdateToCustomerJob((object) ['id' => 1], []);
    $method = new ReflectionMethod(SendUpdateToCustomerJob::class, 'isEmailResponseSuccessful');
    $method->setAccessible(true);

    expect($method->invoke($job, QuoteTypeId::Device, $responseCode))->toBe($expected);
})->with([
    [Response::HTTP_OK, true],
    [Response::HTTP_CREATED, true],
    [Response::HTTP_BAD_REQUEST, false],
]);

test('isEmailResponseSuccessful accepts only HTTP CREATED for non-Device quotes', function (int $responseCode, bool $expected) {
    $job = new SendUpdateToCustomerJob((object) ['id' => 1], []);
    $method = new ReflectionMethod(SendUpdateToCustomerJob::class, 'isEmailResponseSuccessful');
    $method->setAccessible(true);

    expect($method->invoke($job, QuoteTypeId::Car, $responseCode))->toBe($expected);
})->with([
    [Response::HTTP_CREATED, true],
    [Response::HTTP_OK, false],
]);
