<?php

declare(strict_types=1);

use App\Jobs\CourtesyEmailJob;
use App\Services\CourtesyEmailService;
use RuntimeException;

afterEach(function () {
    Mockery::close();
});

test('CourtesyEmailJob does not invoke the service when quoteUID or quoteTypeId is missing', function () {
    $this->mock(CourtesyEmailService::class, function ($mock) {
        $mock->shouldNotReceive('processCourtesyEmailWorkflow');
    });

    $job = new CourtesyEmailJob([]);
    $job->handle(app(CourtesyEmailService::class));
});

test('CourtesyEmailJob does not swallow exceptions so the queue can retry', function () {
    $this->mock(CourtesyEmailService::class, function ($mock) {
        $mock->shouldReceive('processCourtesyEmailWorkflow')
            ->once()
            ->with('quote-uuid', 1)
            ->andThrow(new RuntimeException('Bird API timeout'));
    });

    $job = new CourtesyEmailJob(['quoteUID' => 'quote-uuid', 'quoteTypeId' => 1]);

    expect(fn () => $job->handle(app(CourtesyEmailService::class)))
        ->toThrow(RuntimeException::class, 'Bird API timeout');
});
