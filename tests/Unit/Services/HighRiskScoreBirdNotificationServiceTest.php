<?php

declare(strict_types=1);

use App\Jobs\NotifyHighRiskScoreBirdJob;
use App\Models\QuoteDocument;
use App\Services\HighRiskScoreBirdNotificationService;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Facades\Queue;

test('queues job with customer payload and null riskScoreDoc when upload result is not a quote document', function () {
    Queue::fake();

    $quote = (object) [
        'uuid' => 'u1',
        'code' => 'C1',
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a@b.com',
    ];

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification($quote, 'health', null, 40);

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) {
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty('payload');
        $property->setAccessible(true);
        /** @var array<string, mixed> $payload */
        $payload = $property->getValue($job);

        return ($payload['refId'] ?? null) === 'C1'
            && ($payload['scoreProfile'] ?? null) === 'individual'
            && ($payload['customerEmail'] ?? null) === 'a@b.com'
            && ($payload['customerName'] ?? null) === 'A B'
            && ($payload['riskScore'] ?? null) === 40
            && ($payload['riskScoreDoc'] ?? null) === null;
    });
});

test('queues job with entity score profile for business type', function () {
    Queue::fake();

    $quote = (object) [
        'code' => 'B1',
        'first_name' => 'X',
        'last_name' => 'Y',
        'email' => null,
    ];

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification($quote, 'business', null, 35);

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) {
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty('payload');
        $property->setAccessible(true);
        /** @var array<string, mixed> $payload */
        $payload = $property->getValue($job);

        return ($payload['scoreProfile'] ?? null) === 'entity'
            && ($payload['riskScore'] ?? null) === 35;
    });
});

test('queues job with signed risk score document URL when upload returns QuoteDocument', function () {
    Queue::fake();

    $quote = (object) [
        'uuid' => 'u1',
        'code' => 'C1',
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a@b.com',
    ];

    $pdfUrl = 'https://storage.example.com/container/doc.pdf?sig=abc';

    $this->mock(QuoteDocumentService::class, function ($mock) use ($pdfUrl) {
        $mock->shouldReceive('getDocumentUrl')
            ->once()
            ->with('quotes/risk-doc.pdf', 'azureIMPrivate', 180)
            ->andReturn($pdfUrl);
    });

    $quoteDocument = new QuoteDocument;
    $quoteDocument->doc_url = 'quotes/risk-doc.pdf';

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification(
        $quote,
        'health',
        $quoteDocument,
        41,
    );

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) use ($pdfUrl) {
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty('payload');
        $property->setAccessible(true);
        /** @var array<string, mixed> $payload */
        $payload = $property->getValue($job);

        return ($payload['riskScoreDoc'] ?? null) === $pdfUrl
            && ($payload['riskScore'] ?? null) === 41;
    });
});

test('does not call getDocumentUrl when QuoteDocument has no doc_url', function () {
    Queue::fake();

    $this->mock(QuoteDocumentService::class, function ($mock) {
        $mock->shouldNotReceive('getDocumentUrl');
    });

    $quote = (object) ['code' => 'C1', 'first_name' => null, 'last_name' => null, 'email' => null];
    $quoteDocument = new QuoteDocument;
    $quoteDocument->doc_url = null;

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification($quote, 'health', $quoteDocument, 35);

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) {
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty('payload');
        $property->setAccessible(true);
        /** @var array<string, mixed> $payload */
        $payload = $property->getValue($job);

        return ($payload['riskScoreDoc'] ?? null) === null
            && ($payload['riskScore'] ?? null) === 35;
    });
});
