<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AIGWorkflowJob implements ShouldQueue
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
    public function handle(CarEmailService $carEmailService): void
    {
        try {
            info("AIGWorkflowJob - Starting workflow for Ref ID: {$this->quoteUuid} | Time: ".now());

            LoggerService::startQuoteLogging($this->quoteUuid);

            // Use provided quote type or default to Car if not specified
            $quoteTypeId = $this->quoteTypeId ?? QuoteTypeId::Car;
            $quoteType = QuoteTypes::getName($quoteTypeId);

            if (! $quoteType) {
                info("AIGWorkflowJob - Invalid Quote Type ID: {$quoteTypeId} for Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }

            $quote = $quoteType->model()->where('uuid', $this->quoteUuid)->first();

            if (! $quote) {
                LoggerService::error("AIGWorkflowJob - Quote not found - Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }
            if ($quote->isAIAdvisorAssigned()) {
                // Send AI Advisor Email
                SendAIAdvisorOCBJob::dispatch(QuoteTypes::CAR, $quote->uuid)->delay(Carbon::now()->addMinute());
                LoggerService::info("AIGWorkflowJob - AI Advisor Email sent ");
            }
            else {
                // Use the CarEmailService to send the AIG workflow
                $carEmailService->sendAIGWorkflow($quote);
                LoggerService::info("AIGWorkflowJob - AIG workflow sent ");
            }

            info("AIGWorkflowJob - Completed successfully for Ref ID: {$this->quoteUuid} | Time: ".now());

        } catch (\Throwable $th) {
            info("AIGWorkflowJob - Exception encountered: '{$th->getMessage()}' - Ref ID: {$this->quoteUuid} | Time: ".now());
            Log::error($th);
            throw $th;
        }
    }
}
