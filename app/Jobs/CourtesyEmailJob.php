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

        if (! $quoteUID) {
            LoggerService::error('CourtesyEmailJob - Missing quoteUID', [
                'quoteData' => $this->quoteData,
            ]);

            return;
        }

        if (! $quoteTypeId) {
            $quoteTypeId = $courtesyEmailService->resolveQuoteTypeIdForCourtesyWorkflow($quoteUID);
            if (! $quoteTypeId) {
                LoggerService::error('CourtesyEmailJob - Missing quoteTypeId and could not resolve from quoteUID', [
                    'quoteData' => $this->quoteData,
                ]);

                return;
            }

            LoggerService::info('CourtesyEmailJob - Resolved quoteTypeId for legacy payload', [
                'quoteUID' => $quoteUID,
                'quoteTypeId' => $quoteTypeId,
            ]);
        }

        LoggerService::startQuoteLogging($quoteUID);

        $courtesyEmailService->processCourtesyEmailWorkflow($quoteUID, $quoteTypeId);
    }
}
