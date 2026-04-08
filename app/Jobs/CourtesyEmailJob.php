<?php

namespace App\Jobs;

use App\Services\CourtesyEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CourtesyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 360;
    private $quoteData;

    public function __construct($quoteData)
    {
        $this->quoteData = $quoteData;
    }

    public function handle(CourtesyEmailService $courtesyEmailService): void
    {
        $quoteUID = $this->quoteData['quoteUID'] ?? null;
        $quoteTypeId = $this->quoteData['quoteTypeId'] ?? null;

        if (! $quoteUID || ! $quoteTypeId) {
            LoggerService::error('CourtesyEmailJob - Missing quoteUID or quoteTypeId', [
                'quoteData' => $this->quoteData,
            ]);

            return;
        }

        LoggerService::startQuoteLogging($quoteUID);

        LoggerService::info('CourtesyEmailJob - Processing courtesy email workflow', [
            'quoteUID' => $quoteUID,
            'quoteTypeId' => $quoteTypeId,
        ]);

        $courtesyEmailService->processCourtesyEmailWorkflow($quoteUID, $quoteTypeId);
    }

    /**
     * Log once after all queue retries are exhausted (transient errors are not logged here on each attempt).
     */
    public function failed(?\Throwable $exception): void
    {
        if ($exception === null) {
            return;
        }

        LoggerService::error('CourtesyEmailJob - Failed after all retries', [
            'quoteData' => $this->quoteData,
            'error' => $exception->getMessage(),
        ], $exception);
    }
}
