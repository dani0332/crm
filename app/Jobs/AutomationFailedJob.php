<?php

namespace App\Jobs;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\UserNameEnum;
use App\Enums\WorkflowTypeEnum;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
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
    private $statusAPIFailed;
    private $processInvolved;
    private $workflowType;
    private $userToSendEmail;

    public function __construct($quote, $quoteTypeId, $actionRequired, $statusAPIFailed, $processInvolved, $workflowType, $sendTo = null)
    {
        $this->quote = $quote;
        $this->quoteTypeId = $quoteTypeId;
        $this->actionRequired = $actionRequired;
        $this->processInvolved = $processInvolved;
        $this->statusAPIFailed = $statusAPIFailed;
        $this->workflowType = $workflowType;
        $this->userToSendEmail = $sendTo;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        LoggerService::startQuoteLogging($this->quote);
        LoggerService::info('job:AutomationFailedJob - Job started');
        // $providerName = InsuranceProvidersEnum::getTextByCode($this->quote?->insuranceProvider?->code);
        if ($this->userToSendEmail == UserNameEnum::PA_USER) {
            $this->advisorEmail = $this->quote?->kycDocumentUser?->createdBy?->email;
            $this->advisorName = $this->quote?->kycDocumentUser?->createdBy?->name;
        } else {
            $this->advisorEmail = $this->quote->advisor->email;
            $this->advisorName = $this->quote->advisor->name;
        }

        $emailData = (object) [
            'actionRequired' => $this->actionRequired,
            'advisorEmail' => $this->quote->advisor->email,
            'advisorName' => $this->quote->advisor->name,
            'imcrmReferenceNumber' => $this->quote->code,
            'insurerApiStatus' => $this->statusAPIFailed,
            'insurerName' => $this->quote->first_name.' '.$this->quote->last_name,
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
