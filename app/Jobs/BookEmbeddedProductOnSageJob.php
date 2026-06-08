<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Models\SageProcess;
use App\Services\Logger\LoggerService;
use App\Services\SageApiEmbeddedProductService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Carbon;
use Throwable;

class BookEmbeddedProductOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    use GenericQueriesAllLobs;

    public $tries = 1;
    public $timeout = 80;
    private $sageRequest;
    private $epTransaction;
    private $request;
    private $sageProcess;
    private $lockPostfix;
    private $logFor;

    /**
     * Create a new job instance.
     */
    public function __construct($sageRequest, $epTransaction, $request, $sageProcess)
    {
        $this->sageRequest = $sageRequest;
        $this->epTransaction = $epTransaction;
        $this->request = $request;
        $this->sageProcess = $sageProcess;
        $this->lockPostfix = Carbon::now()->format('YmdHi'); // lock postfix to release the WithoutOverlapping lock i.e 2024102113
    }

    /**
     * Execute the job.
     */
    public function handle(SageApiService $sageApiService)
    {
        $this->logFor = 'BookEmbeddedProductOnSageJob : '.$this->epTransaction->code;
        LoggerService::startQuoteLogging($this->epTransaction, LoggerFeatureEnum::SAGE_EP_BOOKING_REVERSAL);
        LoggerService::info('--------------------------------Sage Embedded Product Booking Job execution started--------------------------------');

        $this->sageProcess = SageProcess::find($this->sageProcess->id);
        $quote = $this->getQuoteObjectBy($this->request->modelType, $this->request->quoteId);

        $quoteTypeId = QuoteTypes::getIdFromValue($this->request->modelType);
        $ePTransaction = $sageApiService->getEPTransactions($quote, $quoteTypeId);
        $isEPTransactionFound = $ePTransaction ? count($ePTransaction) > 0 : false;

        if (! $isEPTransactionFound) {
            $message = 'Cannot proceed as embedded product transaction is not found';
            LoggerService::info($message, extra: ['QuoteCode' => $quote->code]);
            $sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message, $this->logFor);

            return;
        }

        if ($this->sageProcess->status === SageEnum::SAGE_PROCESS_PENDING_STATUS) {
            $sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PROCESSING_STATUS, null, $this->logFor);

            $response = (new SageApiEmbeddedProductService)->bookEmbeddedProductOnSage([$quote, $this->sageRequest, $this->epTransaction], $this->sageRequest->epShortCode);

            if (! $response['status']) {
                $sageErrorLogs = [
                    SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE => 'Sage conflict detected while booking embedded product on Sage - updating status to pending',
                    SageEnum::SAGE_EP_DOCUMENT_NUMBER_ALREADY_EXISTS_MESSAGE => 'EP document already exists error detected while booking embedded product on Sage',
                ];

                if (isset($sageErrorLogs[$response['message']])) {
                    LoggerService::info($sageErrorLogs[$response['message']]);
                    $sageProcessStatus = SageEnum::SAGE_PROCESS_PENDING_STATUS;
                } else {
                    LoggerService::info('Booking embedded product on Sage failed - updating status to failed');
                    $sageProcessStatus = SageEnum::SAGE_PROCESS_FAILED_STATUS;
                }

                $sageApiService->updateSageProcessStatus($this->sageProcess, $sageProcessStatus, $response['message'], $this->logFor);
            } else {
                LoggerService::info('Embedded product booked on Sage - updating status to completed');
                $sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS, null, $this->logFor);
            }
            LoggerService::info('Booking embedded product on Sage completed', extra: ['Response' => json_encode($response)]);
        } else {
            LoggerService::info('Booking embedded product on Sage process skipped', extra: ['SageProcessID' => $this->sageProcess->id, 'Status' => $this->sageProcess->status]);
        }

        $sageApiService->scheduleSageProcesses($this->sageRequest->insurerID);
        LoggerService::info('Schedule Sage processes triggered for insurer - '.$this->sageRequest->insurerID);
        LoggerService::info('--------------------------------Sage Embedded Product Booking Job execution completed--------------------------------');
    }

    public function failed(Throwable $exception)
    {
        $sageApiService = app(SageApiService::class);
        $message = $exception->getMessage();
        $code = $exception->getCode();

        if (str_contains($message, SageEnum::SAGE_TIMEOUT_REQUEST_MESSAGE)) {
            $sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_TIMEOUT_STATUS, $message);
        } else {
            $sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message);
        }

        if ($this->isFailedDueToAttemptsOrTimeout($message)) {
            LoggerService::info('EP Booking : BookEmbeddedProductOnSageJob failed: '.$this->epTransaction->code.' - Code : '.$code.' - Error : '.$message, extra: [
                'errorTraceMessage' => $exception->getTraceAsString(),
            ]);
        } else {
            LoggerService::warning('Booking embedded product on Sage failed', extra: [
                'ErrorCode' => $code,
                'ErrorMessage' => $message,
                'errorTraceMessage' => $exception->getTraceAsString(),
            ]);
        }

        LoggerService::info('Updating Embedded Product booking status to BOOKING_FAILED');
        (new SageApiEmbeddedProductService)->updateAndLogEPBookingStatus($this->epTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), $this->logFor);

        $sageApiService->scheduleSageProcesses($this->sageRequest->insurerID);
        LoggerService::info('Schedule Sage processes triggered for insurer - '.$this->sageRequest->insurerID);
    }

    public function middleware()
    {
        // release the WithoutOverlapping lock 5 minutes after the job has processed
        return [(new WithoutOverlapping($this->epTransaction->code.'-'.$this->lockPostfix))->dontRelease()];
    }

    private function isFailedDueToAttemptsOrTimeout($errorMessage): bool
    {
        $errorMessage = strtolower($errorMessage);

        return str_contains($errorMessage, 'has been attempted too many times') || str_contains($errorMessage, 'has timed out');
    }

}
