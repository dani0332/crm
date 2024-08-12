<?php

namespace App\Jobs;

use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class BookPolicyOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $releaseLockAfter = 10 * 60; // 10 minutes in seconds
    public $timeout = 30;
    private $sageRequest;
    private $quote;
    private $payment;
    private $sageLogArray;
    private $request;

    /**
     * Create a new job instance.
     */
    public function __construct($sageRequest, $quote, $payment, $sageLogArray, $request)
    {
        $this->sageRequest = $sageRequest;
        $this->quote = $quote;
        $this->payment = $payment;
        $this->sageLogArray = $sageLogArray;
        $this->request = $request;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        info('BookPolicyOnSageJob - '.$this->quote->code.' - Started');
        $response = (new SageApiService())->bookPolicyOnSage([$this->sageRequest, $this->quote, $this->payment, $this->sageLogArray, $this->request]);
        info('BookPolicyOnSageJob - '.$this->quote->code.' - Response: '.json_encode($response));
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quote->uuid))->dontRelease()->expireAfter($this->releaseLockAfter)];
    }

}
