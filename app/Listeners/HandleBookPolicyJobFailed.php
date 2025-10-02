<?php

namespace App\Listeners;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\SageEnum;
use App\Jobs\BookPolicyOnSageJob;
use App\Services\Logger\LoggerService;
use App\Services\SageApiService;
use Illuminate\Queue\Events\JobFailed;

class HandleBookPolicyJobFailed
{
    /**
     * Handle the event.
     */
    public function handle(JobFailed $event)
    {
        // Book Polic Job Failure Handling
        if ($event->job->resolveName() == BookPolicyOnSageJob::class) {
            // Get the payload to extract job data
            $payload = $event->job->payload();
            $jobData = unserialize($payload['data']['command']);

            // Extract the necessary properties from the job
            $quote = $jobData->quote;
            $sageProcess = $jobData->sageProcess;
            $sageRequest = $jobData->sageRequest;

            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::SAGE_POLICY_BOOKING);
            LoggerService::info('handleBookPolicyJobFailed fn:handle Policy Book  : Quote Code: '.$quote->code.' - BookPolicyOnSageJob failed due to max attempts -  Setting status to pending instead of failed');

            // Check if this is the "attempted too many times" error
            $errorMessage = $event->exception->getMessage();
            $errorCode = $event->exception->getCode();
            $errorTrace = $event->exception->getTraceAsString();
            if (str_contains($errorMessage, 'has been attempted too many times')) {
                LoggerService::info('handleBookPolicyJobFailed fn:handle - Policy Book : BookPolicyOnSageJob failed due to max attempts - '.$quote->code.' - Setting status to pending instead of failed', extra: [
                    'error' => $errorMessage,
                    'errorCode' => $errorCode,
                    'errorTrace' => $errorTrace,
                ]);

                // Set status to pending instead of failed
                (new SageApiService)->updateSageProcessStatus($sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $event->exception->getMessage());

                // Schedule sage processes to potentially retry later
                (new SageApiService)->scheduleSageProcesses($sageRequest->insurerID);

                LoggerService::info('handleBookPolicyJobFailed fn:handle - Policy Book : Quote Code : '.$quote->code.'  - BookPolicyOnSageJob : scheduleSageProcesses triggered for code - Insurer - '.$sageRequest->insurerID);
            } else {
                LoggerService::error('handleBookPolicyJobFailed fn:handle Policy Book : BookPolicyOnSageJob failed: Quote Code : '.$quote->code.' - Error Code : '.$errorCode.' - Error : '.$errorMessage);
            }
        }


        return;
    }
}
