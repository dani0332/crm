<?php

namespace App\Jobs;

use App\Enums\SageEnum;
use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class SendUpdateSageJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3;
    public $timeout = 5; //40
    public $backoff = 360;

    private $quoteDetails;
    private $payment;
    private $paymentSplit;
    private $payload;
    private $sageAPIsLogs;
    private $extraParams;
    private $sageRequestType;
    private $loginUserDetails;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteDetails, $payment, $paymentSplit, $payload, $sageAPIsLogs, $extraParams, $sageRequestType, $loginUserDetails)
    {
        $this->quoteDetails = $quoteDetails;
        $this->payment = $payment;
        $this->paymentSplit = $paymentSplit;
        $this->payload = $payload;
        $this->sageAPIsLogs = $sageAPIsLogs;
        $this->extraParams = $extraParams;
        $this->sageRequestType = $sageRequestType;
        $this->loginUserDetails = $loginUserDetails;
    }

    /**
     * Execute the job.
     */
    public function handle(SageApiService $sageApiService): void
    {
        info('------------------ Book Update - Sage Job Started - SendUpdateUUID: '.$this->extraParams['send_update_log']->uuid. '------------------');
        $this->extraParams['authDetails'] = $this->loginUserDetails;

        switch ($this->sageRequestType) {
            case SageEnum::SUT_NORMAL:
                $sageResponse = $sageApiService->handleSendUpdateNormalCalls(
                    $this->quoteDetails,
                    $this->payment,
                    $this->paymentSplit,
                    $this->payload,
                    $this->sageAPIsLogs,
                    $this->extraParams
                );
                break;
            
            case SageEnum::SUT_REVE_CORR:
                $sageResponse = $sageApiService->handleSendUpdateRevCorrCalls(
                    $this->quoteDetails,
                    $this->payment,
                    $this->paymentSplit,
                    $this->payload,
                    $this->sageAPIsLogs,
                    $this->extraParams,
                    $this->extraParams['invoicesForReverse']
                );
                break;
        }
        
        $this->setHaystackData('response', $sageResponse, 'array');
    }

}
