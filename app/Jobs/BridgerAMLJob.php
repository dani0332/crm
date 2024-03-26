<?php

namespace App\Jobs;

use App\Services\BridgerInsightService;
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

    /**
     * Create a new job instance.
     */
    public function __construct($bridgerAPIToken, $payload, $quoteDetails, $quoteTypeID, $customerType, $loginCustomerEmail)
    {
        $this->bridgerAPIToken = $bridgerAPIToken;
        $this->payload = $payload;
        $this->quoteDetails = $quoteDetails;
        $this->quoteTypeID = $quoteTypeID;
        $this->customerType = $customerType;
        $this->loginCustomerEmail = $loginCustomerEmail ?? '';
    }

    /**
     * Execute the job.
     */
    public function handle(BridgerInsightService $bridgerInsightService): void
    {
        try {
            $bridgerInsightService->searchAMLResult(
                $this->bridgerAPIToken,
                $this->payload,
                $this->quoteDetails,
                $this->quoteTypeID,
                $this->customerType,
                $this->loginCustomerEmail
            );

        } catch (\Exception $exception) {
            logger()->error('AML Screening Bridger Job Exception: '.$exception->getMessage());
        }
    }
}
