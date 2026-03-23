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

        $courtesyEmailService->processCourtesyEmailWorkflow($quoteUID, $quoteTypeId);
    }
}
