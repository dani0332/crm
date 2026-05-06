<?php

declare(strict_types=1);

use App\Jobs\NotifyHighRiskScoreBirdJob;
use App\Services\HighRiskScoreBirdNotificationService;
use Illuminate\Support\Facades\Queue;

test('dispatches job when score crosses into high risk', function () {
    Queue::fake();

    $quote = (object) [
        'uuid' => 'u1',
        'code' => 'C1',
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a@b.com',
    ];

    app(HighRiskScoreBirdNotificationService::class)->dispatchIfEligible(
        $quote,
        'health',
        ['total' => 40],
        20
    );

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class);
});

test('does not dispatch when score below threshold', function () {
    Queue::fake();

    $quote = (object) [
        'uuid' => 'u1',
        'code' => 'C1',
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => null,
    ];

    app(HighRiskScoreBirdNotificationService::class)->dispatchIfEligible($quote, 'health', ['total' => 20], null);

    Queue::assertNothingPushed();
});

test('does not dispatch when already high risk', function () {
    Queue::fake();

    $quote = (object) [
        'uuid' => 'u1',
        'code' => 'C1',
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => null,
    ];

    app(HighRiskScoreBirdNotificationService::class)->dispatchIfEligible($quote, 'health', ['total' => 40], 36);

    Queue::assertNothingPushed();
});

test('includes risk score PDF temporary URL when provided', function () {
    Queue::fake();

    $quote = (object) [
        'uuid' => 'u1',
        'code' => 'C1',
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a@b.com',
    ];

    $pdfUrl = 'https://storage.example.com/container/doc.pdf?sig=abc';

    app(HighRiskScoreBirdNotificationService::class)->dispatchIfEligible(
        $quote,
        'health',
        ['total' => 40],
        20,
        $pdfUrl,
        'Riskscore_Individual.pdf'
    );

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) use ($pdfUrl) {
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty('payload');
        $property->setAccessible(true);
        /** @var array<string, mixed> $payload */
        $payload = $property->getValue($job);

        return ($payload['risk_score_pdf_url'] ?? null) === $pdfUrl
            && ($payload['risk_score_pdf_filename'] ?? null) === 'Riskscore_Individual.pdf';
    });
});
