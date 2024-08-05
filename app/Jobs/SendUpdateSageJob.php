<?php

namespace App\Jobs;

use App\Enums\SageEnum;
use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Throwable;

class SendUpdateSageJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3; // TODO: Disccuss later
    public $timeout = 40;
    public $backoff = 360;

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

    public function handle(SageApiService $sageApiService): void
    {
        info('------------------ Book Update - Sage Job Started - SendUpdateUUID: '.$this->preparedData['quoteDetails']['uuid'].'------------------');
        $this->preparedData['authDetails'] = $this->loginUserDetails;

        switch ($this->preparedData['sendUpdateType']) {
            case SageEnum::SUT_NORMAL:
                $sageResponse = $sageApiService->handleSendUpdateNormalCalls($this->preparedData, $this->sagePayload, $this->sageAPIsLogs);
                break;

            case SageEnum::SUT_REVE_CORR:
                $sageResponse = $sageApiService->handleSendUpdateRevCorrCalls($this->preparedData, $this->sagePayload, $this->sageAPIsLogs);
                break;
        }
    }

    public function middleware()
    {
        // return [(new WithoutOverlapping($this->newCustomerId))->dontRelease()];
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\OrderShipped  $event
     * @return void
     */
    public function failed(Throwable $exception)
    {
        
    }

}
