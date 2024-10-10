<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Enums\SageEnum;
use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class BookPolicyOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 200 seconds
    public $timeout = 70;
    private $sageRequest;
    private $quote;
    private $request;
    private $sageProcess;

    /**
     * Create a new job instance.
     */
    public function __construct($sageRequest, $quote, $request, $sageProcess)
    {
        $this->sageRequest = $sageRequest;
        $this->quote = $quote;
        $this->request = $request;
        $this->sageProcess = $sageProcess;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - Started');

        (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PROCESSING_STATUS);

        $response = (new SageApiService)->bookPolicyOnSage([$this->sageRequest, $this->quote,  $this->request]);

        if (! $response['status']) {
            $message = $response['message'];
            if ($message == SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE) {
                (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $message);
            } else {
                (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message);

                (new SageApiService)->updateAndLogQuoteStatus($this->quote, $this->sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED, $this->sageRequest->userId);
            }

        } else {
            (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS);
        }

        info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - Response : '.json_encode($response));
        info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - Finished');

        (new SageApiService)->scheduleSageProcesses($this->sageRequest->insurerID);
        info('Policy Book : BookPolicyOnSageJob : scheduleSageProcesses triggered for Insurer - '.$this->sageRequest->insurerID);
    }

    public function failed(Throwable $exception)
    {
        (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $exception->getMessage());

        (new SageApiService)->updateAndLogQuoteStatus($this->quote, $this->sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED, $this->sageRequest->userId);

        (new SageApiService)->scheduleSageProcesses($this->sageRequest->insurerID);
        info('Policy Book : BookPolicyOnSageJob : scheduleSageProcesses triggered for Insurer - '.$this->sageRequest->insurerID);

        Log::error('Policy Book : BookPolicyOnSageJob : '.$this->quote->code.' Error : '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quote->code))->dontRelease()];
    }

}
