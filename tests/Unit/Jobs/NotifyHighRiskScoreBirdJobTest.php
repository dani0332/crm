<?php

declare(strict_types=1);

use App\Enums\WorkflowTypeEnum;
use App\Jobs\NotifyHighRiskScoreBirdJob;
use App\Services\EmailServices\WebEngageService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

test('sends WebEngage event with the payload and logs success when workflow responds 201', function () {
    Log::spy();

    $toRecipient = fake()->unique()->safeEmail();

    $payload = [
        'refId' => 'HEA-1',
        'scoreProfile' => 'individual',
        'customerId' => $toRecipient,
        'customerEmail' => $toRecipient,
        'firstName' => fake()->firstName(),
        'lastName' => fake()->lastName(),
        'customerMobile' => fake()->e164PhoneNumber(),
        'riskScoreDoc' => null,
        'riskScore' => 40,
        'ccRecipient' => fake()->unique()->safeEmail(),
    ];

    $webEngageService = $this->mock(WebEngageService::class, function ($mock) use ($payload) {
        $mock->shouldReceive('sendEvent')
            ->once()
            ->with(WorkflowTypeEnum::HIGH_RISK_NOTIFICATION, $payload)
            ->andReturn((object) ['status_code' => Response::HTTP_CREATED, 'body' => [], 'headers' => []]);
    });

    $job = new NotifyHighRiskScoreBirdJob($payload);
    $job->handle($webEngageService);

    Log::shouldHaveReceived('info')->once();
    Log::shouldNotHaveReceived('warning');
});

test('logs a warning when WebEngage workflow does not respond 201', function () {
    Log::spy();

    $payload = [
        'refId' => 'C1',
        'scoreProfile' => 'individual',
        'customerId' => fake()->unique()->safeEmail(),
        'riskScore' => 40,
    ];

    $webEngageService = $this->mock(WebEngageService::class, function ($mock) {
        $mock->shouldReceive('sendEvent')
            ->once()
            ->andReturn((object) ['status_code' => 500, 'body' => [], 'headers' => []]);
    });

    $job = new NotifyHighRiskScoreBirdJob($payload);
    $job->handle($webEngageService);

    Log::shouldHaveReceived('warning')->once();
});

test('logs a warning with exception context when the job fails after retries', function () {
    Log::spy();

    $job = new NotifyHighRiskScoreBirdJob(['refId' => 'C1', 'riskScore' => 40]);

    $job->failed(new Exception('boom'));

    Log::shouldHaveReceived('warning')->once();
});
