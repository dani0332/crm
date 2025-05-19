<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\SageEnum;
use App\Services\Logger\LoggerService;
use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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
    public function __construct($request, $paymentSplit, $sageRequest, $sageProcess)
    {
        $this->sageApiService = new SageApiService;
        $this->paymentSplit = $paymentSplit;
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
        info(self::class.' fn: '.__FUNCTION__.' : paymentSplitID : '.$this->paymentSplit->id.' - Started');

        $this->sageProcess = $this->sageProcess->refresh();

        if ($this->sageProcess->status === SageEnum::SAGE_PROCESS_PENDING_STATUS) {
            $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PROCESSING_STATUS, null, 'PostPrepaymentToSageJob : paymentSplitID : '.$this->paymentSplit->id);

            $response = $this->sageApiService->postPrepaymentToSage([$this->paymentSplit, $this->sageRequest,  $this->request]);

            if (! $response['status']) {
                $message = $response['message'];
                if ($message == SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE) {
                    info(self::class.' fn: '.__FUNCTION__.' : paymentSplitID :  '.$this->paymentSplit->id.' - sage conflict - updating status to pending', [
                        'sageProcessId' => $this->sageProcess->id,
                    ]);
                    $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $message, 'PostPrepaymentToSageJob : paymentSplitID : '.$this->paymentSplit->id);
                } else {
                    info(self::class.' fn: '.__FUNCTION__.' paymentSplitID - '.$this->paymentSplit->id.' - posting failed - updating status to failed');
                    $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message, 'PostPrepaymentToSageJob : paymentSplitID : '.$this->paymentSplit->id);
                }

            } else {
                info(self::class.' fn: '.__FUNCTION__.': paymentSplitID  - '.$this->paymentSplit->id.' - Prepayment posted - updating status to completed');
                $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS, null, 'PostPrepaymentToSageJob : paymentSplitID : '.$this->paymentSplit->id);
            }

            info(self::class.' fn: '.__FUNCTION__.' PostPrepaymentToSage : paymentSplitID  - '.$this->paymentSplit->id.' - Finished . Response : ', $response);
        } else {
            info(self::class.' fn: '.__FUNCTION__.'job:PostPrepaymentToSage - Process Skipped - Process ID: '.$this->sageProcess->id.' - Status : '.$this->sageProcess->status);
        }

        $this->sageApiService->scheduleSageProcesses($this->sageRequest->insurerID);
        info(self::class.' fn: '.__FUNCTION__.' PostPrepaymentToSage : paymentSplitID  : scheduleSageProcesses triggered for  code -'.$this->paymentSplit->id.'Insurer - '.$this->sageRequest->insurerID);
    }

    public function failed(Throwable $exception)
    {
        $message = $exception->getMessage();

        if (str_contains($message, SageEnum::SAGE_TIMEOUT_REQUEST_MESSAGE)) {
            $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_TIMEOUT_STATUS, $message);
        } else {
            $this->sageApiService->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message);
        }

        Log::error(self::class.' fn: '.__FUNCTION__.' : paymentSplitID  : '.$this->paymentSplit->id.' Error : '.$message);

        $this->sageApiService->scheduleSageProcesses($this->sageRequest->insurerID);
        info(self::class.' fn: '.__FUNCTION__.' : scheduleSageProcesses fn:failed triggered for paymentSplitID -'.$this->paymentSplit->id.' Insurer - '.$this->sageRequest->insurerID);

    }

    public function middleware()
    {
        // release the WithoutOverlapping lock 5 minutes after the job has processed
        return [(new WithoutOverlapping($this->paymentSplit->id.'-'.$this->lockPostfix))->dontRelease()];
    }
}
