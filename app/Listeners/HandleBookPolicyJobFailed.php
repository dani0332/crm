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
        // Check if this is our specific job
        if ($event->job->resolveName() !== BookPolicyOnSageJob::class) {
            return;
        }

        // Get the payload to extract job data
        $payload = $event->job->payload();
        $jobData = unserialize($payload['data']['command']);

        // Extract the necessary properties from the job
        $quote = $jobData->quote;
        $sageProcess = $jobData->sageProcess;
        $sageRequest = $jobData->sageRequest;

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::SAGE_POLICY_BOOKING);
        LoggerService::info('handleBookPolicyJobFailed fn:handle Policy Book  : Quote Code'.$quote->code.' - BookPolicyOnSageJob failed due to max attempts -  Setting status to pending instead of failed');

        // Check if this is the "attempted too many times" error
        if (str_contains($event->exception->getMessage(), 'has been attempted too many times')) {
            LoggerService::info('handleBookPolicyJobFailed fn:handle Policy Book : BookPolicyOnSageJob failed due to max attempts - '.$quote->code.' - Setting status to pending instead of failed');

            // Set status to pending instead of failed
            (new SageApiService)->updateSageProcessStatus($sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $event->exception->getMessage());

            // Schedule sage processes to potentially retry later
            (new SageApiService)->scheduleSageProcesses($sageRequest->insurerID);

            LoggerService::info('handleBookPolicyJobFailed fn:handle Policy Book : BookPolicyOnSageJob : scheduleSageProcesses triggered for code - '.$quote->code.' Insurer - '.$sageRequest->insurerID);
        }
    }
}