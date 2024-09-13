<?php

namespace App\Jobs;

use App\Services\SageApiService;
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
    public function handle(): void
    {
        info('job:SendUpdateSageJob - Send Update Sage Job Process Start -  QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);

        $response = (new SageApiService)->bookEndorsementOnSage([
            $this->requestPayload,
            $this->sendUpdateLog,
            $this->sageRequestPayload,
            $this->preparedData,
        ]);

        info('job:SendUpdateSageJob - Send Update Sage Job Process Completed - QuoteType: '.$this->requestPayload->quoteType.' - QuoteUUID: '.$this->requestPayload->quoteUuid.' - SendUpdateUUID: '.$this->sendUpdateLog->uuid);
    }

    public function failed(Throwable $exception): void
    {
        info('job:SendUpdateSageJob - SendUpdateUUID: '.$this->sendUpdateLog->uuid.' - Error : '.$exception->getMessage());
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->sendUpdateLog->uuid))->releaseAfter($this->releaseAfter)];
    }

}
