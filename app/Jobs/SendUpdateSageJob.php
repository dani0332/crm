<?php

namespace App\Jobs;

use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Services\SageApiService;
use App\Services\SendUpdateLogService;
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

    // 30 seconds
    public int $timeout = 70;
    private $requestPayload;
    private $sendUpdateLog;
    private $sageRequestPayload;
    private $sageProcess;

    /**
     * Create a new job instance.
     */
    public function __construct($requestPayload, $sendUpdateLog, $sageRequestPayload, $sageProcess)
    {
        $this->requestPayload = $requestPayload;
        $this->sendUpdateLog = $sendUpdateLog;
        $this->sageRequestPayload = $sageRequestPayload;
        $this->sageProcess = $sageProcess;
    }

    /**
     * Execute the job.
     */
    public function handle(SageApiService $sageApiService, SendUpdateLogService $sendUpdateLogService): void
    {
        info('job:SendUpdateSageJob - Process Start - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);

        $sageApiService->updateSageProcessStatus(sageProcess: $this->sageProcess, status: SageEnum::SAGE_PROCESS_PROCESSING_STATUS, logFor: 'Endorsement Booking Sage Process');
        $this->sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED]);

        try {
            $response = $sageApiService->bookEndorsementOnSage([
                $this->requestPayload,
                $this->sendUpdateLog,
                $this->sageRequestPayload,
            ]);
            $this->setResponses($response, $this->sageProcess, $this->sendUpdateLog);
        } catch (\Exception $e) {
            logger()->error('job:SendUpdateSageJob - Error: '.$e->getMessage());
            $response = ['status' => false, 'message' => $e->getMessage()];
            $this->setResponses($response, $this->sageProcess, $this->sendUpdateLog);
        }

        info('job:SendUpdateSageJob - Response: '.json_encode($response).' - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);
        info('job:SendUpdateSageJob - Process Completed - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);

        (new SageApiService)->scheduleSageProcesses($this->requestPayload->insurerID);
        info('job:SendUpdateSageJob - fn:ScheduleSageProcesses triggered for Insurer:'.$this->requestPayload->insurerID);
    }

    private function setResponses($response, $sageProcess, $sendUpdateLog): void
    {
        if (! $response['status']) {
            if ($response['message'] == SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE) {
                (new SageApiService)->updateSageProcessStatus($sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $response['message']);
            } else {
                (new SageApiService)->updateSageProcessStatus($sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $response['message']);
                $sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
            }

        } else {
            (new SageApiService)->updateSageProcessStatus($sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS);
        }
    }

    public function failed(Throwable $exception): void
    {
        (new SageApiService)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $exception->getMessage());
        $this->sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
        info('job:SendUpdateSageJob - SendUpdateUUID: '.$this->sendUpdateLog->uuid.' - Error : '.$exception->getMessage());

        (new SageApiService)->scheduleSageProcesses($this->requestPayload->insurerID);
        info('job:SendUpdateSageJob - fn:ScheduleSageProcesses triggered for Insurer:'.$this->requestPayload->insurerID);
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->sendUpdateLog->uuid))->dontRelease()];
    }
}
