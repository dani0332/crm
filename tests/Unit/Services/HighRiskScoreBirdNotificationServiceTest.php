<?php

declare(strict_types=1);

use App\Jobs\NotifyHighRiskScoreBirdJob;
use App\Models\ApplicationStorage;
use App\Models\QuoteDocument;
use App\Services\HighRiskScoreBirdNotificationService;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

function getHighRiskJobPayload(NotifyHighRiskScoreBirdJob $job): array
{
    $reflection = new ReflectionClass($job);
    $property = $reflection->getProperty('payload');
    $property->setAccessible(true);

    /** @var array<string, mixed> */
    return $property->getValue($job);
}

test('does not queue job and logs a warning when to recipient is not configured', function () {
    Queue::fake();
    Log::spy();

    $quote = (object) ['code' => 'C1', 'first_name' => 'A', 'last_name' => 'B', 'phone' => '0500000000'];

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification($quote, 'health', null, 40);

    Queue::assertNotPushed(NotifyHighRiskScoreBirdJob::class);
    Log::shouldHaveReceived('warning')->once();
});

test('queues job with recipient, customer, and score details when to recipient is configured', function () {
    Queue::fake();

    ApplicationStorage::factory()->highRiskScoreNotificationToRecipient('aml.compliance@insurancemarket.ae')->createOne();
    ApplicationStorage::factory()->highRiskScoreNotificationCcRecipient('mohamed.faisal@insurancemarket.ae')->createOne();

    $quote = (object) [
        'code' => 'C1',
        'first_name' => 'A',
        'last_name' => 'B',
        'phone' => '0500000000',
    ];

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification($quote, 'health', null, 40);

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) {
        $payload = getHighRiskJobPayload($job);

        return ($payload['refId'] ?? null) === 'C1'
            && ($payload['scoreProfile'] ?? null) === 'individual'
            && ($payload['customerId'] ?? null) === 'aml.compliance@insurancemarket.ae'
            && ($payload['customerEmail'] ?? null) === 'aml.compliance@insurancemarket.ae'
            && ($payload['firstName'] ?? null) === 'A'
            && ($payload['lastName'] ?? null) === 'B'
            && ($payload['customerMobile'] ?? null) === '0500000000'
            && ($payload['ccRecipient'] ?? null) === 'mohamed.faisal@insurancemarket.ae'
            && ($payload['riskScore'] ?? null) === 40
            && ($payload['riskScoreDoc'] ?? null) === null;
    });
});

test('queues job with entity score profile for business type', function () {
    Queue::fake();

    ApplicationStorage::factory()->highRiskScoreNotificationToRecipient()->createOne();

    $quote = (object) ['code' => 'B1', 'first_name' => 'X', 'last_name' => 'Y', 'phone' => null];

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification($quote, 'business', null, 35);

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) {
        $payload = getHighRiskJobPayload($job);

        return ($payload['scoreProfile'] ?? null) === 'entity'
            && ($payload['riskScore'] ?? null) === 35;
    });
});

test('queues job with signed risk score document URL when upload returns QuoteDocument', function () {
    Queue::fake();

    ApplicationStorage::factory()->highRiskScoreNotificationToRecipient()->createOne();

    $pdfUrl = 'https://storage.example.com/container/doc.pdf?sig=abc';

    $this->mock(QuoteDocumentService::class, function ($mock) use ($pdfUrl) {
        $mock->shouldReceive('getDocumentUrl')
            ->once()
            ->with('quotes/risk-doc.pdf', 'azureIMPrivate', 180)
            ->andReturn($pdfUrl);
    });

    $quoteDocument = new QuoteDocument;
    $quoteDocument->doc_url = 'quotes/risk-doc.pdf';

    $quote = (object) ['code' => 'C1', 'first_name' => 'A', 'last_name' => 'B', 'phone' => null];

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification(
        $quote,
        'health',
        $quoteDocument,
        41,
    );

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) use ($pdfUrl) {
        $payload = getHighRiskJobPayload($job);

        return ($payload['riskScoreDoc'] ?? null) === $pdfUrl
            && ($payload['riskScore'] ?? null) === 41;
    });
});

test('does not call getDocumentUrl when QuoteDocument has no doc_url', function () {
    Queue::fake();

    ApplicationStorage::factory()->highRiskScoreNotificationToRecipient()->createOne();

    $this->mock(QuoteDocumentService::class, function ($mock) {
        $mock->shouldNotReceive('getDocumentUrl');
    });

    $quote = (object) ['code' => 'C1', 'first_name' => null, 'last_name' => null, 'phone' => null];
    $quoteDocument = new QuoteDocument;
    $quoteDocument->doc_url = null;

    app(HighRiskScoreBirdNotificationService::class)->queueHighRiskBirdNotification($quote, 'health', $quoteDocument, 35);

    Queue::assertPushed(NotifyHighRiskScoreBirdJob::class, function (NotifyHighRiskScoreBirdJob $job) {
        $payload = getHighRiskJobPayload($job);

        return ($payload['riskScoreDoc'] ?? null) === null
            && ($payload['riskScore'] ?? null) === 35;
    });
});
