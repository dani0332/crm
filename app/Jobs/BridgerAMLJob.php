<?php

namespace App\Jobs;

use App\Services\BridgerInsightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BridgerAMLJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 40;
    public $backoff = 360;
    private $payload;
    private $quoteRequestID;
    private $quoteTypeID;
    private $customerType;
    private $bridgerAPIToken;
    private $loginCustomerEmail;

    /**
     * Create a new job instance.
     */
    public function __construct($bridgerAPIToken, $payload, $quoteRequestID, $quoteTypeID, $customerType, $loginCustomerEmail)
    {
        $this->bridgerAPIToken = $bridgerAPIToken;
        $this->payload = $payload;
        $this->quoteRequestID = $quoteRequestID;
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
            info('AML Screening Bridger Job - AML Screening run with Code '.$this->payload['code'].' - Data : '.json_encode($this->payload).'. Triggered By:'.$this->loginCustomerEmail);
            $bridgerInsightService->searchAMLResult($this->bridgerAPIToken, $this->payload, $this->quoteRequestID, $this->quoteTypeID, $this->customerType, $this->loginCustomerEmail);
        } catch (\Exception $exception) {
            info('AML Screening Bridger Job Exception: '.$exception->getMessage());
            Log::error($exception);
        }
    }
}
