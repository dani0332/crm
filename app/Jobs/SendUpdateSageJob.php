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

    public int $tries = 1;
    public int $releaseAfter = 30; // 30 seconds
    public int $timeout = 30;
    private $requestPayload;
    private $sendUpdateLog;
    private $sageRequestPayload;
    private $preparedData;
    private $sageProcess;

    /**
     * Create a new job instance.
     */
    public function __construct($requestPayload, $sendUpdateLog, $sageRequestPayload, $preparedData, $sageProcess)
    {
        $this->requestPayload = $requestPayload;
        $this->sendUpdateLog = $sendUpdateLog;
        $this->sageRequestPayload = $sageRequestPayload;
        $this->preparedData = $preparedData;
        $this->sageProcess = $sageProcess;
    }

    /**
     * Execute the job.
     */
    public function handle(SageApiService $sageApiService, SendUpdateLogService $sendUpdateLogService): void
    {
        info('job:SendUpdateSageJob - Process Start -  QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);

        $this->updateSageProcessStatus(SageEnum::SAGE_PROCESS_PROCESSING_STATUS);
        $this->sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED]);

        $response = $sageApiService->bookEndorsementOnSage([
            $this->requestPayload,
            $this->sendUpdateLog,
            $this->sageRequestPayload,
            $this->preparedData,
        ]);

        if (! $response['status']) {
            $this->updateSageProcessStatus(SageEnum::SAGE_PROCESS_FAILED_STATUS, $response['message']);
            $this->sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
        } else {
            $this->updateSageProcessStatus(SageEnum::SAGE_PROCESS_COMPLETED_STATUS);
        }

        info('job:SendUpdateSageJob - Response: '.json_encode($response).' - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);
        info('job:SendUpdateSageJob - Process Completed - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);
    }

    public function failed(Throwable $exception): void
    {
        $this->updateSageProcessStatus(SageEnum::SAGE_PROCESS_FAILED_STATUS, $exception->getMessage());
        $this->sendUpdateLog->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
        //            TODO:: Need to add message why it's failed

        info('job:SendUpdateSageJob - SendUpdateUUID: '.$this->sendUpdateLog->uuid.' - Error : '.$exception->getMessage());
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->sendUpdateLog->uuid))->releaseAfter($this->releaseAfter)];
    }

    private function updateSageProcessStatus($status, $message = null): void
    {
        $sageProcessData['status'] = $status;
        if ($message) {
            $sageProcessData['message'] = $message;
        }

        $this->sageProcess->update($sageProcessData);
        info('BookPolicyOnSageJob - updateSageProcessStatus - ID : '.$this->sageProcess->id.' - Status : '.$status);
    }

}
