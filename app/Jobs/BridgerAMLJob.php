<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\BridgerInsightService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BridgerAMLJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 40;
    public $backoff = 360;
    private $payload;
    private $quoteDetails;
    private $quoteTypeID;
    private $customerType;
    private $bridgerAPIToken;
    private $loginCustomerEmail;
    private $isAutomation;

    /**
     * Create a new job instance.
     */
    public function __construct($bridgerAPIToken, $payload, $quoteDetails, $quoteTypeID, $customerType, $loginCustomerEmail, $isAutomation = false)
    {
        $this->bridgerAPIToken = $bridgerAPIToken;
        $this->payload = $payload;
        $this->quoteDetails = $quoteDetails;
        $this->quoteTypeID = $quoteTypeID;
        $this->customerType = $customerType;
        $this->loginCustomerEmail = $loginCustomerEmail ?? '';
        $this->isAutomation = $isAutomation;
    }

    /**
     * Execute the job.
     */
    public function handle(BridgerInsightService $bridgerInsightService): void
    {
        LoggerService::startQuoteLogging($this->quoteDetails, LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info('BridgerAMLJob started');

        try {
            $bridgerInsightService->searchAMLResult(
                $this->bridgerAPIToken,
                $this->payload,
                $this->quoteDetails,
                $this->quoteTypeID,
                $this->customerType,
                $this->loginCustomerEmail,
                isAutomation: $this->isAutomation
            );

        } catch (\Exception $exception) {
            LoggerService::error('AML Screening Bridger Job failed', exception: $exception);
        }

        LoggerService::info('BridgerAMLJob ended');
    }
}
