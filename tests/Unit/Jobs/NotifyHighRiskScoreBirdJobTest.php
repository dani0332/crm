<?php

declare(strict_types=1);

use App\Exceptions\HighRiskBirdNotificationFailedException;
use App\Jobs\NotifyHighRiskScoreBirdJob;
use App\Models\ApplicationStorage;
use App\Services\BirdService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    ApplicationStorage::withTrashed()->chunkById(100, function ($rows): void {
        foreach ($rows as $row) {
            $row->forceDelete();
        }
    });
});

test('invokes bird webhook when workflow url is stored', function () {
    ApplicationStorage::factory()
        ->birdHighRiskAmlScoreNotificationWorkflow()
        ->createOne();

    $payload = [
        'refId' => 'HEA-1',
        'scoreProfile' => 'individual',
        'customerEmail' => null,
        'customerName' => null,
        'riskScoreDoc' => null,
        'riskScore' => 40,
    ];

    $bird = $this->mock(BirdService::class, function ($mock) use ($payload) {
        $mock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->with('https://bird.example/flow', $payload)
            ->andReturn((object) ['status_code' => 200, 'body' => '', 'headers' => []]);
    });

    $job = new NotifyHighRiskScoreBirdJob($payload);
    $job->handle($bird);
});

test('throws when bird returns non-200 so the queue can retry', function () {
    ApplicationStorage::factory()
        ->birdHighRiskAmlScoreNotificationWorkflow()
        ->createOne();

    $payload = [
        'refId' => 'C1',
        'scoreProfile' => 'individual',
        'customerEmail' => null,
        'customerName' => null,
        'riskScoreDoc' => null,
        'riskScore' => 40,
    ];

    $bird = $this->mock(BirdService::class, function ($mock) use ($payload) {
        $mock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->with('https://bird.example/flow', $payload)
            ->andReturn((object) ['status_code' => 503, 'body' => 'upstream', 'headers' => []]);
    });

    $job = new NotifyHighRiskScoreBirdJob($payload);

    $job->handle($bird);
})->throws(HighRiskBirdNotificationFailedException::class);

test('skips bird when workflow url missing', function () {
    $bird = $this->mock(BirdService::class, function ($mock) {
        $mock->shouldNotReceive('triggerWebHookRequest');
    });

    $job = new NotifyHighRiskScoreBirdJob([
        'refId' => 'x',
        'scoreProfile' => 'individual',
        'customerEmail' => null,
        'customerName' => null,
        'riskScoreDoc' => null,
        'riskScore' => 40,
    ]);
    $job->handle($bird);
});
