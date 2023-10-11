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

    protected $payload;
    protected $quoteRequestID;
    protected $quoteTypeID;
    protected $customerType;
    protected $bridgerAPIToken;
    protected $bridgerInsightService;

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
        $this->bridgerInsightService = new BridgerInsightService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            info('Bridger Insight Service - AML Check with Code '.$this->payload['code'].' - Data : '.json_encode($this->payload));
            $this->bridgerInsightService->searchAMLResult($this->bridgerAPIToken, $this->payload, $this->quoteRequestID, $this->quoteTypeID, $this->customerType);

        } catch (\Exception $exception) {
            info('complete exception :  '.json_encode($exception));
            info('Bridger Insight Service Job is timed out now at: '.now());
            info('Exception: '.$exception->getMessage());
            Log::error($exception);
        }
    }
}
