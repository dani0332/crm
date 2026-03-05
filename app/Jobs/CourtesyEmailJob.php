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

    /**
     * Create a new job instance.
     */
    public function __construct($quoteData)
    {
        $this->quoteData = $quoteData;
    }

    /**
     * Execute the job.
     */
    public function handle(CourtesyEmailService $courtesyEmailService): void
    {
        if (isset($this->quoteData['quoteUID'])) {
            LoggerService::startQuoteLogging($this->quoteData['quoteUID']);
        }

        $quoteUID = $this->quoteData['quoteUID'] ?? null;
        $quoteTypeId = $this->quoteData['quoteTypeId'] ?? null;

        if (! $quoteUID || ! $quoteTypeId) {
            LoggerService::error('CourtesyEmailJob - Missing required data', [
                'quoteData' => $this->quoteData,
            ]);

            return;
        }

        $result = $courtesyEmailService->processCourtesyEmailWorkflow($quoteUID, $quoteTypeId);

        LoggerService::info('Courtesy Email Job - Result', [
            'quoteUID' => $quoteUID,
            'quoteTypeId' => $quoteTypeId,
            'result' => $result,
        ]);
    }
}
