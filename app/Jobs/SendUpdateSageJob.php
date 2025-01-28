<?php

namespace App\Jobs;

use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SageProcess;
use App\Services\CentralService;
use App\Services\SageApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendUpdateSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public int $timeout = 80;
    private $requestPayload;
    private $sendUpdateLog;
    private $sageRequestPayload;
    private $sageProcess;
    private $lockPostfix;

    /**
     * Create a new job instance.
     */
    public function __construct($requestPayload, $sendUpdateLog, $sageRequestPayload, $sageProcess)
    {
        $this->requestPayload = $requestPayload;
        $this->sendUpdateLog = $sendUpdateLog;
        $this->sageRequestPayload = $sageRequestPayload;
        $this->sageProcess = $sageProcess;
        $this->lockPostfix = Carbon::now()->format('YmdHi'); // lock postfix to release the WithoutOverlapping lock i.e 2024102113
    }

    /**
     * Execute the job.
     */
    public function handle(SageApiService $sageApiService): void
    {
        info('job:SendUpdateSageJob - Process Start - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);

        $this->sageProcess = SageProcess::find($this->sageProcess->id);

        if ($this->sageProcess->status === SageEnum::SAGE_PROCESS_PENDING_STATUS) {
            $sageApiService->updateSageProcessStatus(sageProcess: $this->sageProcess, status: SageEnum::SAGE_PROCESS_PROCESSING_STATUS, logFor: 'Endorsement Booking Sage Process : '.$this->sendUpdateLog->uuid);
            app(CentralService::class)->updateSendUpdateStatusLogs($this->sendUpdateLog->id, $this->sendUpdateLog->status, SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED);
            $this->sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED]);

            $response = $sageApiService->bookEndorsementOnSage([
                $this->requestPayload,
                $this->sendUpdateLog,
                $this->sageRequestPayload,
            ]);

            if (! $response['status']) {
                $message = $response['message'];
                if ($message == SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE) {
                    info('job:SendUpdateSageJob - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid.' sage conflict - updating status to pending');
                    (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $message, 'SendUpdateUUID: '.$this->sendUpdateLog->uuid);
                } else {
                    info('job:SendUpdateSageJob - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid.' booking failed - updating status to failed');
                    (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message, 'SendUpdateUUID: '.$this->sendUpdateLog->uuid);
                    app(CentralService::class)->updateSendUpdateStatusLogs($this->sendUpdateLog->id, $this->sendUpdateLog->status, SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED);
                    $this->sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
                }

            } else {
                info('job:SendUpdateSageJob - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid.' booking completed - updating status to completed');
                (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS, null, 'SendUpdateUUID: '.$this->sendUpdateLog->uuid);
            }

            info('job:SendUpdateSageJob - Response: '.json_encode($response).' - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);
            info('job:SendUpdateSageJob - Process Completed - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);

        } else {
            info('job:SendUpdateSageJob - Sage Process Skipped - Process ID: '.$this->sageProcess->id.' - Status : '.$this->sageProcess->status);
        }

        (new SageApiService)->scheduleSageProcesses($this->sageRequestPayload->insurerID);
        info('job:SendUpdateSageJob - fn:ScheduleSageProcesses triggered for Insurer: '.$this->sageRequestPayload->insurerID);

    }

    public function failed(Throwable $exception): void
    {
        $message = $exception->getMessage();

        if (str_contains($message, SageEnum::SAGE_TIMEOUT_REQUEST_MESSAGE)) {
            (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_TIMEOUT_STATUS, $message);
        } else {
            (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message);
        }
        info('job:SendUpdateSageJob - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid.' fn:failed - updating status to failed');

        app(CentralService::class)->updateSendUpdateStatusLogs($this->sendUpdateLog->id, $this->sendUpdateLog->status, SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED);
        $this->sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
        info('job:SendUpdateSageJob - SendUpdateUUID: '.$this->sendUpdateLog->uuid.' - Error : '.$message);

        (new SageApiService)->scheduleSageProcesses($this->sageRequestPayload->insurerID);
        info('job:SendUpdateSageJob - fn:ScheduleSageProcesses triggered for Insurer:'.$this->sageRequestPayload->insurerID);
    }

    public function middleware(): array
    {
        // release the WithoutOverlapping lock 5 minutes after the job has processed
        return [(new WithoutOverlapping($this->sendUpdateLog->uuid.'-'.$this->lockPostfix))->dontRelease()];
    }
}
