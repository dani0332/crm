<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\EP\SendEPJob;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\SageApiService;
use App\Services\SendEmailCustomerService;
use App\Services\SendUpdateLogService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AutomationFailedJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 100;
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    private $quote;
    private $quoteTypeId;
    private $actionRequired;
    private $advisorEmail;
    private $advisorName;
    private $imcrmReferenceNumber;
    private $insurerApiStatus;
    private $insurerName;
    private $processInvolved;
    private $workflowType;

    public function __construct($quote, $quoteTypeId, $actionRequired, $advisorEmail, $advisorName, $imcrmReferenceNumber, $insurerApiStatus, $insurerName, $processInvolved, $workflowType)
    {
        $this->quote = $quote;
        $this->quoteTypeId = $quoteTypeId;
        $this->actionRequired = $actionRequired;
        $this->advisorEmail = $advisorEmail;
        $this->advisorName = $advisorName;
        $this->imcrmReferenceNumber = $imcrmReferenceNumber;
        $this->insurerApiStatus = $insurerApiStatus;
        $this->insurerName = $insurerName;
        $this->processInvolved = $processInvolved;
        $this->workflowType = $workflowType;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        LoggerService::startQuoteLogging($this->quote);
        LoggerService::info('job:AutomationFailedJob - Job started');

        $emailData = (object) [
            'actionRequired' => $this->actionRequired,
            'advisorEmail' => $this->advisorEmail,
            'advisorName' => $this->advisorName,
            'imcrmReferenceNumber' => $this->imcrmReferenceNumber,
            'insurerApiStatus' => $this->insurerApiStatus,
            'insurerName' => $this->insurerName,
            'processInvolved' => $this->processInvolved,
            'workflowType' => $this->workflowType,
        ];

        $response = app(CentralService::class)->sendAutomationEmail($this->quote, $emailData, $this->quoteTypeId, WorkflowTypeEnum::CAR_AUTOMATION_FAILED);
        LoggerService::info('job:AutomationFailedJob - Job Response ', extra: ['emailData' => json_encode($response)]);

        if ($response == 200) {
            LoggerService::info('job:AutomationFailedJob - email sent successfully');
        } else {
            LoggerService::info('job:AutomationFailedJob - Job failed');
        }

        LoggerService::info('job:AutomationFailedJob - Job completed - Quote Code: '.$this->quote->code);
    }

    public function failed(Throwable $exception)
    {
        LoggerService::info('job:AutomationFailedJob - Quote Code: '.$this->quote->code.' Error: '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quote->id))->dontRelease()];
    }
}
