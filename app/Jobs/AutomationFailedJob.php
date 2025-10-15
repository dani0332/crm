<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Enums\UserNameEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\QuoteType;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class AutomationFailedJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 100;
    public int $tries = 3;
    private $quoteId;
    private $quoteTypeId;
    private $actionRequired;
    private $recipientEmail;
    private $recipientName;
    private $statusAPIFailed;
    private $processInvolved;
    private $workflowType;
    private $userToSendEmail;
    private $appEnv;
    private $insuranceProvider;
    private $insurerName = '';

    public function __construct($quoteId, $quoteTypeId, $actionRequired, $statusAPIFailed, $processInvolved, $workflowType, $sendTo = null)
    {
        LoggerService::info('job:AutomationFailedJob - Initializing job', extra: [
            'quoteId' => $quoteId,
            'quoteTypeId' => $quoteTypeId,
        ]);
        $this->quoteId = $quoteId;
        $this->quoteTypeId = $quoteTypeId;
        $this->actionRequired = $actionRequired;
        $this->processInvolved = $processInvolved;
        $this->statusAPIFailed = $statusAPIFailed;
        $this->workflowType = $workflowType;
        $this->userToSendEmail = $sendTo;
        $this->appEnv = config('constants.APP_ENV');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $quoteType = QuoteTypes::getName($this->quoteTypeId)->value;
        $quote = $this->getQuoteObject($quoteType, $this->quoteId);

        LoggerService::startQuoteLogging($quote);
        LoggerService::info('job:AutomationFailedJob - Job started', extra: [
            'userToSendEmail' => $this->userToSendEmail,
            'quoteCode' => $quote->code ?? 'unknown',
        ]);

        $payment = $quote->payments()->mainLeadPayment()->first();
        $this->insuranceProvider = getInsuranceProvider($payment, $quoteType);
        $this->insurerName = InsuranceProvidersEnum::getTextByCode($this->insuranceProvider?->code);

        if ($this->userToSendEmail == UserNameEnum::PA_USER) {
            $this->recipientEmail = $quote?->kycDocumentUser?->createdBy?->email;
            $this->recipientName = $quote?->kycDocumentUser?->createdBy?->name;
        } else {
            if ($quote?->advisor) {
                $this->recipientEmail = $quote->advisor->email;
                $this->recipientName = $quote->advisor->name;
            } else {
                LoggerService::info('job:AutomationFailedJob - No advisor assigned, stopping job - Insurer: '.$this->insurerName);

                return;
            }
        }

        $cc['approvalemail'] = null;
        $cc['prodemail'] = null;
        $cc['advisoremail'] = null;
        if (in_array($this->appEnv, [EnvEnum::PRODUCTION, EnvEnum::STAGING])) {
            $approvalEmail = getAppStorageValueByKey(ApplicationStorageEnums::APPROVAL_PRODUCTION_EMAIL);
            $prodEmail = getAppStorageValueByKey(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL);
            $cc['approvalemail'] = $approvalEmail;
            $cc['prodemail'] = $prodEmail;
            $cc['advisoremail'] = $this->quote?->advisor?->email ?? '';
        }

        if (! $this->recipientEmail || ! $this->recipientName) {
            LoggerService::info('job:AutomationFailedJob - Recipient details missing, stopping job - Insurer: '.$this->insurerName);

            return;
        }

        $emailData = (object) [
            'actionRequired' => $this->actionRequired,
            'recipientEmail' => $this->recipientEmail,
            'recipientName' => $this->recipientName,
            'imcrmReferenceNumber' => $quote->code,
            'insurerApiStatus' => $this->statusAPIFailed,
            'insurerName' => $this->insuranceProvider?->text ?? '',
            'processInvolved' => $this->processInvolved,
            'cc' => $cc,
            'workflowType' => $this->workflowType,
        ];

        $response = app(CentralService::class)->sendAutomationEmail($quote, $emailData, $this->quoteTypeId, WorkflowTypeEnum::CAR_AUTOMATION_FAILED);
        LoggerService::info('job:AutomationFailedJob - Job Response ', extra: ['emailData' => json_encode($response)]);

        if ($response == 200) {
            LoggerService::info('job:AutomationFailedJob - email sent successfully - Insurer: '.$this->insurerName);
        } else {
            LoggerService::info('job:AutomationFailedJob - Job failed - Insurer: '.$this->insurerName, extra: [
                'response' => json_encode($response),
            ]);
        }

        LoggerService::info('job:AutomationFailedJob - Job completed - Quote Code: '.$quote->code);
    }

    public function failed(Exception $ex)
    {
        LoggerService::error('job:AutomationFailedJob - Insurer: '.($this->insurerName).' Failed', exception: $ex);
    }

    public function middleware()
    {
        LoggerService::info('job:AutomationFailedJob - Middleware setup', extra: ['quoteId' => $this->quoteId]);

        return [(new WithoutOverlapping($this->quoteId.'-automation'))->dontRelease()];
    }
}
