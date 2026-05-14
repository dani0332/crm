<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\Logger\LoggerService;
use App\Services\MyAlfredWelcomeEmailInboundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessMyAlfredWelcomeEmailSqsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 60;

    public function __construct(
        public string $email,
        public string $code,
        public ?string $source = null,
        public ?string $tag = null,
    ) {
        $this->onConnection('sqs_myalfred');
    }

    public function handle(MyAlfredWelcomeEmailInboundService $welcomeEmailInboundService): void
    {
        // Mark logs as coming from the SQS inbound consumer so it's distinguishable from API-origin logs.
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SQS_INBOUND_QUEUE);
        LoggerService::info('ProcessMyAlfredWelcomeEmailSqsJob started');

        $welcomeEmailInboundService->process(
            $this->email,
            $this->code,
            $this->source,
            $this->tag,
        );
        LoggerService::endLogging();
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(Throwable $exception): void
    {
        // Ensure feature context is set for SQS inbound failures.
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SQS_INBOUND_QUEUE);
        LoggerService::error('ProcessMyAlfredWelcomeEmailSqsJob failed', [], $exception, [
            'email' => $this->email,
            'code' => $this->code,
            'source' => $this->source,
            'tag' => $this->tag,
        ]);
        LoggerService::endLogging();
    }
}
