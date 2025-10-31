<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\SageEnum;
use App\Models\PaymentSplits;
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

class PostPrepaymentToSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 80;
    private $sageRequest;
    private $request;
    private $sageProcess;
    private $paymentSplit;
    private $lockPostfix;
    private $sageApiService;

    /**
     * Create a new job instance.
     */
    public function __construct($request, $sageRequest, $sageProcess)
    {
        $this->sageApiService = new SageApiService;
        $this->sageRequest = $sageRequest;
        $this->request = $request;
        $this->sageProcess = $sageProcess;
        $this->lockPostfix = Carbon::now()->format('YmdHi'); // lock postfix to release the WithoutOverlapping lock i.e 2024102113
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SAGE_POST_PREPAYMENT);
        LoggerService::info('--------------------------------Sage Post Prepayment Job execution started--------------------------------', extra: [
            'SageProcessID' => $this->sageProcess->id,
        ]);

        $this->sageProcess = SageProcess::find($this->sageProcess->id);
        $this->paymentSplit = PaymentSplits::find($this->sageProcess->model_id);

        LoggerService::info('Fetched payment split', extra: ['PaymentSplitID' => $this->paymentSplit?->id]);

        if ($this->sageProcess->status === SageEnum::SAGE_PROCESS_PENDING_STATUS) {
            $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PROCESSING_STATUS, null, 'PostPrepaymentToSageJob : paymentSplitID : '.$this->paymentSplit->id);

            $response = $this->sageApiService->postPrepaymentToSage([$this->paymentSplit, $this->sageRequest,  $this->request]);

            if (! $response['status']) {
                $message = $response['message'];
                if ($message == SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE) {
                    LoggerService::info('Sage conflict detected while posting prepayment to Sage - updating status to pending', extra: [
                        'PaymentSplitID' => $this->paymentSplit?->id,
                    ]);
                    $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $message, 'PostPrepaymentToSageJob : paymentSplitID : '.$this->paymentSplit->id);
                } else {
                    LoggerService::info('Posting prepayment to Sage failed - updating status to failed', extra: [
                        'PaymentSplitID' => $this->paymentSplit?->id,
                    ]);
                    $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message, 'PostPrepaymentToSageJob : paymentSplitID : '.$this->paymentSplit->id);
                }

            } else {
                LoggerService::info('Prepayment posted to Sage - updating status to completed', extra: [
                    'PaymentSplitID' => $this->paymentSplit?->id,
                ]);
                $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS, null, 'PostPrepaymentToSageJob : paymentSplitID : '.$this->paymentSplit->id);
            }

            LoggerService::info('Posting prepayment to Sage completed', extra: ['Response' => json_encode($response), 'PaymentSplitID' => $this->paymentSplit?->id]);
        } else {
            LoggerService::info('Posting prepayment to Sage process skipped', extra: ['SageProcessID' => $this->sageProcess->id, 'Status' => $this->sageProcess->status]);
        }

        $this->sageApiService->scheduleSageProcesses($this->sageRequest->insurerID);
        LoggerService::info('Schedule Sage processes triggered for insurer - '.$this->sageRequest->insurerID, extra: ['PaymentSplitID' => $this->paymentSplit?->id]);
        LoggerService::info('--------------------------------Sage Post Prepayment Job execution completed--------------------------------');
    }

    public function failed(Throwable $exception)
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();

        if (str_contains($message, SageEnum::SAGE_TIMEOUT_REQUEST_MESSAGE)) {
            $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_TIMEOUT_STATUS, $message, 'PostPrepaymentToSageJob : SageProcessID : '.$this->sageProcess->id);
        } else {
            $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message, 'PostPrepaymentToSageJob : SageProcessID : '.$this->sageProcess->id);
        }

        if ($this->isFailedDueToAttemptsOrTimeout($message)) {
            LoggerService::info('Posting prepayment to Sage failed', extra: [
                'ErrorCode' => $code,
                'ErrorMessage' => $message,
                'SageProcessID' => $this->sageProcess->id,
            ]);
        } else {
            LoggerService::warning('Posting prepayment to Sage failed', extra: [
                'ErrorCode' => $code,
                'ErrorMessage' => $message,
                'SageProcessID' => $this->sageProcess->id,
            ]);
        }

        $this->sageApiService->scheduleSageProcesses($this->sageRequest->insurerID);
        LoggerService::info('Schedule Sage processes triggered for insurer - '.$this->sageRequest->insurerID, extra: ['SageProcessID' => $this->sageProcess->id]);
        LoggerService::info('--------------------------------Sage Post Prepayment Job execution completed--------------------------------');
    }

    public function middleware()
    {
        // release the WithoutOverlapping lock 5 minutes after the job has processed
        return [(new WithoutOverlapping($this->sageProcess->id.'-'.$this->lockPostfix))->dontRelease()];
    }

    private function isFailedDueToAttemptsOrTimeout($errorMessage): bool
    {
        $errorMessage = strtolower($errorMessage);

        return str_contains($errorMessage, 'has been attempted too many times') || str_contains($errorMessage, 'has timed out');
    }
}
