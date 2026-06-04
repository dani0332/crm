<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\SageEnum;
use App\Models\SageProcess;
use App\Services\Logger\LoggerService;
use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Throwable;

class BookPolicyOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 80;
    public $sageRequest;
    public $quote;
    public $request;
    public $sageProcess;
    public $lockPostfix;

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
    public function handle(SageApiService $sageApiService)
    {
        LoggerService::startQuoteLogging($this->quote, LoggerFeatureEnum::SAGE_POLICY_BOOKING);
        LoggerService::info('--------------------------------Sage Policy Booking Job execution started--------------------------------');

        $this->sageProcess = SageProcess::find($this->sageProcess->id);

        if ($this->sageProcess->status === SageEnum::SAGE_PROCESS_PENDING_STATUS) {
            $sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PROCESSING_STATUS, null, self::class.' : '.$this->quote->code);

            $response = $sageApiService->bookPolicyOnSage([$this->sageRequest, $this->quote,  $this->request]);

            if (! $response['status']) {

                $sageErrorLogs = [
                    SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE => 'Sage conflict detected while booking policy on Sage - updating status to pending',
                    SageEnum::SAGE_EP_DOCUMENT_NUMBER_ALREADY_EXISTS_MESSAGE => 'EP document already exists on Sage - updating status to pending',
                ];

                if (isset($sageErrorLogs[$response['message']])) {
                    LoggerService::info($sageErrorLogs[$response['message']]);
                    $sageProcessStatus = SageEnum::SAGE_PROCESS_PENDING_STATUS;
                } else {
                    LoggerService::info('Booking policy on Sage failed - updating status to failed');
                    $sageProcessStatus = SageEnum::SAGE_PROCESS_FAILED_STATUS;
                    $sageApiService->updateAndLogQuoteStatus($this->quote, $this->sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED);
                }

                $sageApiService->updateSageProcessStatus($this->sageProcess, $sageProcessStatus, $response['message'], self::class.' : '.$this->quote->code);
            } else {
                LoggerService::info('Policy booked on Sage - updating status to completed');
                $sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS, null, self::class.' : '.$this->quote->code);
            }

            LoggerService::info('Booking policy on Sage completed', extra: ['Response' => json_encode($response)]);
        } else {
            LoggerService::info('Booking policy on Sage skipped', extra: ['SageProcessID' => $this->sageProcess->id, 'Status' => $this->sageProcess->status]);
        }

        $sageApiService->scheduleSageProcesses($this->sageRequest->insurerID);
        LoggerService::info('Schedule Sage processes triggered for insurer - '.$this->sageRequest->insurerID);
        LoggerService::info('--------------------------------Sage Policy Booking Job execution completed--------------------------------');
    }

    public function failed(Throwable $exception)
    {
        $sageApiService = app(SageApiService::class);
        $message = $exception->getMessage();
        $code = $exception->getCode();
        $sageProcessStatus = SageEnum::SAGE_PROCESS_FAILED_STATUS;
        if (str_contains($message, SageEnum::SAGE_TIMEOUT_REQUEST_MESSAGE) || $this->isFailedDueToAttemptsOrTimeout($message)) {
            $sageProcessStatus = SageEnum::SAGE_PROCESS_TIMEOUT_STATUS;
            if ($this->isFailedDueToAttemptsOrTimeout($message)) {
                // Set status to pending instead of failed when job has been attempted too many times
                $sageProcessStatus = SageEnum::SAGE_PROCESS_PENDING_STATUS;
            }
            LoggerService::info('Booking policy on Sage failed', extra: [
                'ErrorCode' => $code,
                'ErrorMessage' => $message,
                'errorTraceMessage' => $exception->getTraceAsString(),
            ]);
        } else {
            LoggerService::warning('Booking policy on Sage failed', extra: [
                'ErrorCode' => $code,
                'ErrorMessage' => $message,
                'errorTraceMessage' => $exception->getTraceAsString(),
            ]);
        }

        LoggerService::info('Updating Sage Process status to '.$sageProcessStatus, extra: ['SageProcessID' => $this->sageProcess->id]);
        $sageApiService->updateSageProcessStatus($this->sageProcess, $sageProcessStatus, $message);

        LoggerService::info('Updating Quote status to POLICY_BOOKING_FAILED', extra: ['QuoteCode' => $this->quote->code]);
        $sageApiService->updateAndLogQuoteStatus($this->quote, $this->sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED);

        $sageApiService->scheduleSageProcesses($this->sageRequest->insurerID);
        LoggerService::info('Schedule Sage processes triggered for insurer - '.$this->sageRequest->insurerID);
    }

    public function middleware()
    {
        // release the WithoutOverlapping lock 5 minutes after the job has processed
        return [(new WithoutOverlapping($this->quote->code.'-'.$this->lockPostfix))->dontRelease()];
    }

    private function isFailedDueToAttemptsOrTimeout($errorMessage): bool
    {
        $errorMessage = strtolower($errorMessage);

        return str_contains($errorMessage, 'has been attempted too many times') || str_contains($errorMessage, 'has timed out');
    }

}
