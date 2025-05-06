<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Services\EmailServices\TravelEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TravelAIGWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $quoteUuid;

    private $quoteTypeId;
    public $tries = 3;
    public $timeout = 15;
    public $backoff = 60;

    public function __construct($quoteUuid, $quoteTypeId = null)
    {
        $this->quoteUuid = $quoteUuid;
        $this->quoteTypeId = $quoteTypeId;
    }

    /**
     * Execute the job.
     */
    public function handle(TravelEmailService $travelEmailService): void
    {
        try {
            info("TravelAIGWorkflowJob - Starting workflow for Ref ID: {$this->quoteUuid} | Time: ".now());

            LoggerService::startQuoteLogging($this->quoteUuid);

            // Use provided quote type or default to Travel if not specified
            $quoteTypeId = $this->quoteTypeId ?? QuoteTypeId::Travel;
            $quoteType = QuoteTypes::getName($quoteTypeId);

            if (! $quoteType) {
                info("TravelAIGWorkflowJob - Invalid Quote Type ID: {$quoteTypeId} for Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }

            $quote = $quoteType->model()->where('uuid', $this->quoteUuid)->first();

            if (! $quote) {
                info("TravelAIGWorkflowJob - Quote not found - Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }

            // Use the TravelEmailService to send the AIG workflow
            $travelEmailService->sendAIGWorkflow($quote);

            info("TravelAIGWorkflowJob - Completed successfully for Ref ID: {$this->quoteUuid} | Time: ".now());

        } catch (\Throwable $th) {
            info("TravelAIGWorkflowJob - Exception encountered: '{$th->getMessage()}' - Ref ID: {$this->quoteUuid} | Time: ".now());
            Log::error($th);
            throw $th;
        }
    }
} 