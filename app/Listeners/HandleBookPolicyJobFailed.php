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
    public const LOG_PREFIX = 'handleBookPolicyJobFailed fn:handle - Policy Book : BookPolicyOnSageJob';
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

            $exception = $event->exception;
            $errorMessage = $exception->getMessage();
            $errorCode = $exception->getCode();
            $errorTrace = $exception->getTraceAsString();

            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::SAGE_POLICY_BOOKING);
            LoggerService::info(self::LOG_PREFIX.'  failed due to '.$errorMessage.' -  Setting status to pending instead of failed');

            // Check if this is the "attempted too many times" error

            $shouldReattempt = str_contains($errorMessage, 'has been attempted too many times') || str_contains($errorMessage, 'has timed out');
            if ($shouldReattempt) {
                LoggerService::info(self::LOG_PREFIX.' failed due to '.$errorMessage.' - Setting status to pending instead of failed', extra: [
                    'error' => $errorMessage,
                    'errorCode' => $errorCode,
                    'errorTrace' => $errorTrace,
                ]);

                // Set status to pending instead of failed
                (new SageApiService)->updateSageProcessStatus($sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $errorMessage);

                // Schedule sage processes to potentially retry later
                (new SageApiService)->scheduleSageProcesses($sageRequest->insurerID);

                LoggerService::info(self::LOG_PREFIX.' : scheduleSageProcesses triggered for code - Insurer - '.$sageRequest->insurerID);
            } else {
                LoggerService::error(self::LOG_PREFIX.'  failed: Quote Code : Error Code : '.$errorCode.' - Error : '.$errorMessage);
            }
        }

    }
}
