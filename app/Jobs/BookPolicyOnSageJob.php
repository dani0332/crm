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
use Throwable;

class BookPolicyOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $releaseAfter = 30; // 30 seconds
    public $timeout = 30;
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
        info('BookPolicyOnSageJob - '.$this->quote->code.' - Started');

        (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PROCESSING_STATUS);

        $response = (new SageApiService)->bookPolicyOnSage([$this->sageRequest, $this->quote,  $this->request]);

        if (! $response['status']) {
            $message = $response['message'];
            if ($message == SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE) {
                (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS , $message);
            } else {
                (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message);

                (new SageApiService)->updateAndLogQuoteStatus($this->quote, $this->sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_QUEUED, $this->quote->userId);
            }

        } else {
            (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS);
        }

        info('BookPolicyOnSageJob - '.$this->quote->code.' - Response : '.json_encode($response));
        info('BookPolicyOnSageJob - '.$this->quote->code.' - Finished');
    }

    public function failed(Throwable $exception)
    {
        (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $exception->getMessage());

        (new SageApiService)->updateAndLogQuoteStatus($this->quote, $this->sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_QUEUED, $this->quote->userId);

        info('BookPolicyOnSageJob : '.$this->quote->code.' Error : '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quote->code))->releaseAfter($this->releaseAfter)];
    }


}
