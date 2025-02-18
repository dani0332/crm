<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Enums\SageEnum;
use App\Models\SageProcess;
use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Log;
use Throwable;

class BookPolicyOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 80;
    private $sageRequest;
    private $quote;
    private $request;
    private $sageProcess;
    private $lockPostfix;

    /**
     * Create a new job instance.
     */
    public function __construct($sageRequest, $quote, $request, $sageProcess)
    {
        $this->sageRequest = $sageRequest;
        $this->quote = $quote;
        $this->request = $request;
        $this->sageProcess = $sageProcess;
        $this->lockPostfix = Carbon::now()->format('YmdHi'); // lock postfix to release the WithoutOverlapping lock i.e 2024102113
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - Started');

        $this->sageProcess = SageProcess::find($this->sageProcess->id);

        if ($this->sageProcess->status === SageEnum::SAGE_PROCESS_PENDING_STATUS) {
            (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PROCESSING_STATUS, null, 'BookPolicyOnSageJob : '.$this->quote->code);

            $response = (new SageApiService)->bookPolicyOnSage([$this->sageRequest, $this->quote,  $this->request]);

            if (! $response['status']) {
                $message = $response['message'];
                if ($message == SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE) {
                    info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - sage conflict - updating status to pending');
                    (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $message, 'BookPolicyOnSageJob : '.$this->quote->code);
                } else {
                    info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - booking failed - updating status to failed');
                    (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message, 'BookPolicyOnSageJob : '.$this->quote->code);

                    (new SageApiService)->updateAndLogQuoteStatus($this->quote, $this->sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED, $this->sageRequest->userId);
                }

            } else {
                info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - policy booked - updating status to completed');
                (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS, null, 'BookPolicyOnSageJob : '.$this->quote->code);
            }

            info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - Response : '.json_encode($response));
            info('Policy Book : BookPolicyOnSageJob - '.$this->quote->code.' - Finished');
        } else {
            info('job:SendUpdateSageJob - Process Skipped - QuoteType: '.$this->sageProcess->id.' - Status : '.$this->sageProcess->status);
        }

        (new SageApiService)->scheduleSageProcesses($this->sageRequest->insurerID);
        info('Policy Book : BookPolicyOnSageJob : scheduleSageProcesses triggered for  code -'.$this->quote->code.'Insurer - '.$this->sageRequest->insurerID);
    }

    public function failed(Throwable $exception)
    {
        $message = $exception->getMessage();

        if (str_contains($message, SageEnum::SAGE_TIMEOUT_REQUEST_MESSAGE)) {
            (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_TIMEOUT_STATUS, $message);
        } else {
            (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message);
        }

        Log::error('Policy Book : BookPolicyOnSageJob : '.$this->quote->code.' Error : '.$message);

        info('Policy Book : BookPolicyOnSageJob : scheduleSageProcesses fn:failed triggered for code -'.$this->quote->code.' updating status to failed');
        (new SageApiService)->updateAndLogQuoteStatus($this->quote, $this->sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED, $this->sageRequest->userId);

        (new SageApiService)->scheduleSageProcesses($this->sageRequest->insurerID);
        info('Policy Book : BookPolicyOnSageJob : scheduleSageProcesses fn:failed triggered for code -'.$this->quote->code.' Insurer - '.$this->sageRequest->insurerID);

    }

    public function middleware()
    {
        // release the WithoutOverlapping lock 5 minutes after the job has processed
        return [(new WithoutOverlapping($this->quote->code.'-'.$this->lockPostfix))->dontRelease()];
    }

}
