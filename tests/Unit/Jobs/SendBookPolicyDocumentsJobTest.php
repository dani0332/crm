<?php

declare(strict_types=1);

use App\Jobs\SendBookPolicyDocumentsJob;

test('resolvePayloadAdvisorId falls back to quote advisor_id when payload has no advisor keys', function () {
    $payload = (object) [
        'model_type' => 'car',
        'quote_id' => 1,
    ];
    $quote = (object) ['advisor_id' => 77];

    $job = new SendBookPolicyDocumentsJob($payload, 'CODE');
    $method = new ReflectionMethod(SendBookPolicyDocumentsJob::class, 'resolvePayloadAdvisorId');
    $method->setAccessible(true);

    expect($method->invoke($job, $quote))->toBe(77);
});

test('resolvePayloadAdvisorId prefers advisorId on payload over quote advisor_id', function () {
    $payload = (object) [
        'model_type' => 'car',
        'quote_id' => 1,
        'advisorId' => 5,
    ];
    $quote = (object) ['advisor_id' => 77];

    $job = new SendBookPolicyDocumentsJob($payload, 'CODE');
    $method = new ReflectionMethod(SendBookPolicyDocumentsJob::class, 'resolvePayloadAdvisorId');
    $method->setAccessible(true);

    expect($method->invoke($job, $quote))->toBe(5);
});
