<?php

namespace App\Jobs;

use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class SendUpdateSageJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3;
    public $timeout = 5; //40
    public $backoff = 360;
    private $quoteDetails;
    private $payload;
    private $payment;
    private $paymentSplit;
    private $extraParams;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteDetails, $payload, $payment, $paymentSplit, $extraParams)
    {
        $this->quoteDetails = $quoteDetails;
        $this->payload = $payload;
        $this->payment = $payment;
        $this->paymentSplit = $paymentSplit;
        $this->extraParams = $extraParams;
    }

    /**
     * Execute the job.
     */
    public function handle(SageApiService $sageApiService): void
    {
        $sageResponse = $sageApiService->handleSendUpdateCalls($this->quoteDetails, $this->payload, $this->payment, $this->paymentSplit, $this->extraParams);

        $this->setHaystackData('sageResponse', $sageResponse, 'array');

    }

}
