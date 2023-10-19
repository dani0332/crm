<?php

namespace App\Jobs;

use App\Services\BridgerInsightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AMLEmailsJob implements ShouldQueue
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

    /**
     * Create a new job instance.
     */
    public function __construct($bridgerAPIToken, $payload, $quoteRequestID, $quoteTypeID, $customerType)
    {
        $this->bridgerAPIToken = $bridgerAPIToken;
        $this->payload = $payload;
        $this->quoteRequestID = $quoteRequestID;
        $this->quoteTypeID = $quoteTypeID;
        $this->customerType = $customerType;
    }

    /**
     * Execute the job.
     */
    public function handle(BridgerInsightService $bridgerInsightService): void
    {
        try {
            info('BridgerAMLJob - AML Check with Code '.$this->payload['code'].' - Data : '.json_encode($this->payload));
            $bridgerInsightService->searchAMLResult($this->bridgerAPIToken, $this->payload, $this->quoteRequestID, $this->quoteTypeID, $this->customerType);
        } catch (\Exception $exception) {
            info('BridgerAMLJob Exception: '.$exception->getMessage());
            Log::error($exception);
        }
    }
}
