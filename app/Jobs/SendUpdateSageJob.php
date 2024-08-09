<?php

namespace App\Jobs;

use App\Enums\SageEnum;
use App\Services\SageApiService;
use App\Services\SendUpdateLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SendUpdateSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3; // TODO: Disccuss later
    public $timeout = 40;
    public $backoff = 360;
    // public $releaseLockAfter = 600; // 10 minutes

    private $preparedData;
    private $sagePayload;
    private $sageAPIsLogs;
    private $loginUserDetails;

    /**
     * Create a new job instance.
     */
    // TODO: NNeed to refactor this constructor
    public function __construct($preparedData, $sagePayload, $sageAPIsLogs, $loginUserDetails)
    {
        $this->preparedData = $preparedData;
        $this->sagePayload = $sagePayload;
        $this->sageAPIsLogs = $sageAPIsLogs;
        $this->loginUserDetails = $loginUserDetails;
    }

    /**
     * Execute the job.
     */

    public function handle(SageApiService $sageApiService, SendUpdateLogService $sendUpdateLogService): void
    {
        info('------------------ Book Update - Sage Job Execution Start - QuoteType: '.$this->preparedData['quoteType'].' - QuoteUUID: '.$this->preparedData['quoteDetails']['uuid'].' - SendUpdateUUID: '.$this->preparedData['sendUpdateLog']['uuid'].' ------------------');
        $this->preparedData['authDetails'] = $this->loginUserDetails;

        switch ($this->preparedData['sendUpdateType']) {
            case SageEnum::SUT_NORMAL:
                $sageApiService->handleStraightDocumentsERP($this->preparedData, $this->sagePayload, $this->sageAPIsLogs);
                break;

            case SageEnum::SUT_REVE_CORR:
                // $sageApiService->handleReveralDocumentsERP($this->preparedData, $this->sagePayload, $this->sageAPIsLogs);
                break;
        }

        info('------------------ Book Update - Sage Job Execution Completed - QuoteType: '.$this->preparedData['quoteType'].' - QuoteUUID: '.$this->preparedData['quoteDetails']['uuid'].' - SendUpdateUUID: '.$this->preparedData['sendUpdateLog']['uuid'].' ------------------');
    }

}
