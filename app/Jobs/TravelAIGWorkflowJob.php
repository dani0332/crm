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
            LoggerService::info("TravelAIGWorkflowJob - Starting workflow");

            // Use provided quote type or default to Travel if not specified
            $quoteTypeId = $this->quoteTypeId ?? QuoteTypeId::Travel;
            $quoteType = QuoteTypes::getName($quoteTypeId);

            if (! $quoteType) {
                LoggerService::info("TravelAIGWorkflowJob - Invalid Quote Type");

                return;
            }

            $quote = $quoteType->model()->where('uuid', $this->quoteUuid)->first();

            if (! $quote) {
                LoggerService::info("TravelAIGWorkflowJob - Quote not found");

                return;
            }

            // Use the TravelEmailService to send the AIG workflow
            $travelEmailService->sendTravelAIGWorkflow($quote);

            LoggerService::info("TravelAIGWorkflowJob - Completed successfully");

        } catch (\Throwable $th) {
            LoggerService::error("TravelAIGWorkflowJob - Exception encountered", exception: $th);
            throw $th;
        }
    }
} 