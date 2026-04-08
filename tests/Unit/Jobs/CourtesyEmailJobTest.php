<?php

declare(strict_types=1);

use App\Jobs\CourtesyEmailJob;
use App\Services\CourtesyEmailService;

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
