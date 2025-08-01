<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Services\EmailServices\TravelEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TravelAIGWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;

    public $timeout = 15;
    public $backoff = 60;

    public function __construct(protected $quoteUuid, protected $quoteType) {}

    /**
     * Execute the job.
     */
    public function handle(TravelEmailService $travelEmailService): void
    {
        LoggerService::startQuoteLogging($this->quoteType->refId($this->quoteUuid));
        try {
            LoggerService::info('TravelAIGWorkflowJob - Starting workflow');

            // Use provided quote type or default to Travel if not specified
            $quoteType = $this->quoteType ?? QuoteTypes::TRAVEL;

            if (! $quoteType) {
                LoggerService::info('TravelAIGWorkflowJob - Invalid Quote Type');

                return;
            }

            $quote = $quoteType->model()->where('uuid', $this->quoteUuid)->first();

            if (! $quote) {
                LoggerService::info('TravelAIGWorkflowJob - Quote not found');

                return;
            }

            // Use the TravelEmailService to send the AIG workflow
            $travelEmailService->sendTravelAIGWorkflow($quote);

            LoggerService::info('TravelAIGWorkflowJob - Completed successfully');

        } catch (\Exception $e) {
            LoggerService::error('TravelAIGWorkflowJob - Exception encountered', exception: $e);
            throw $e;
        }
    }
}
