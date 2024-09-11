<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Enums\SageEnum;
use App\Models\QuoteStatusLog;
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

        $this->updateSageProcessStatus(SageEnum::SAGE_PROCESS_PROCESSING_STATUS);

        $this->updateAndLogQuoteStatus(QuoteStatusEnum::POLICY_BOOKING_QUEUED);

        $response = (new SageApiService)->bookPolicyOnSage([$this->sageRequest, $this->quote,  $this->request]);

        if (! $response['status']) {
            $this->updateSageProcessStatus(SageEnum::SAGE_PROCESS_FAILED_STATUS);

            $this->updateAndLogQuoteStatus(QuoteStatusEnum::POLICY_BOOKING_FAILED);
        } else {
            $this->updateSageProcessStatus(SageEnum::SAGE_PROCESS_COMPLETED_STATUS);
        }

        info('BookPolicyOnSageJob - '.$this->quote->code.' - Response : '.json_encode($response));
        info('BookPolicyOnSageJob - '.$this->quote->code.' - Finished');
    }

    public function failed(Throwable $exception)
    {
        $this->updateSageProcessStatus(SageEnum::SAGE_PROCESS_FAILED_STATUS, $exception->getMessage());

        $this->updateAndLogQuoteStatus(QuoteStatusEnum::POLICY_BOOKING_FAILED);

        info('BookPolicyOnSageJob : '.$this->quote->code.' Error : '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quote->uuid))->releaseAfter($this->releaseAfter)];
    }

    private function updateAndLogQuoteStatus($quoteStatusId)
    {
        $quoteTypeId = $this->sageRequest->quoteTypeId;
        $userId = $this->quote->userId;

        $latestQuoteStatusLog = QuoteStatusLog::where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $this->quote->id,
        ])->latest()->first();

        unset($this->quote->userId);

        $previousQuoteStatusId = $this->quote->quote_status_id;
        $newQuoteStatusId = $quoteStatusId;

        $this->quote->update([
            'quote_status_id' => $newQuoteStatusId,
            'quote_status_date' => now(),
        ]);

        info('BookPolicyOnSageJob - updateAndLogQuoteStatus - Status : '.$this->quote->code.', - Status : '.$newQuoteStatusId);

        $quoteLogData = [
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $this->quote->id,
            'current_quote_status_id' => $newQuoteStatusId,
            'previous_quote_status_id' => $previousQuoteStatusId,
            'created_by' => $userId,
        ];

        $isQuoteLogSameAsBefore = $latestQuoteStatusLog->current_quote_status_id == QuoteStatusEnum::PolicyBooked && $latestQuoteStatusLog->previous_quote_status_id = $previousQuoteStatusId;
        //check if the last quote log status is same as new status then update the same log
        if ($latestQuoteStatusLog && $isQuoteLogSameAsBefore) {
            $latestQuoteStatusLog->update($quoteLogData);
        } else {
            QuoteStatusLog::create($quoteLogData);
        }
    }

    private function updateSageProcessStatus($status, $message = null)
    {
        $sageProcessData['status'] = $status;
        if ($message) {
            $sageProcessData['message'] = $message;
        }

        $this->sageProcess->update($sageProcessData);
        info('BookPolicyOnSageJob - updateSageProcessStatus - ID : '.$this->sageProcess->id.' - Status : '.$status);
    }

}
