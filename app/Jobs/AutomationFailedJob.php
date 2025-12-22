<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\UserNameEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\User;
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
    private const CYBER_BOOKING_TEAM_EMAIL = 'production.approval.team@insurancemarket.ae';
    private const CYBER_BOOKING_TEAM_NAME = 'Production Approval Team';
    private const FALLBACK_CYBER_FAILURE_DISTRIBUTION = [
        'dt.system.notifications@insurancemarket.ae',
        'cyber.enquiries@insurancemarket.ae',
        'sandeep.sharma@insurancemarket.ae',
        'diya.lekhwani@myalfred.com',
        'digital.transformation.support@myalfred.com',
    ];
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
        $quoteTypeEnum = QuoteTypes::getName($this->quoteTypeId);
        $quoteType = $quoteTypeEnum?->value ?? QuoteTypes::CAR->value;
        $quote = $this->getQuoteObject($quoteType, $this->quoteId);

        if (! $quote) {
            LoggerService::error('job:AutomationFailedJob - Quote not found', extra: [
                'quoteId' => $this->quoteId,
                'quoteTypeId' => $this->quoteTypeId,
                'quoteType' => $quoteType,
            ]);

            return;
        }

        LoggerService::startQuoteLogging($quote);
        LoggerService::info('job:AutomationFailedJob - Job started', extra: [
            'userToSendEmail' => $this->userToSendEmail,
            'quoteCode' => $quote->code ?? 'unknown',
        ]);

        $payment = $quote->payments()->mainLeadPayment()->first();
        $this->insuranceProvider = getInsuranceProvider($payment, $quoteType);
        $this->insurerName = InsuranceProvidersEnum::getTextByCode($this->insuranceProvider?->code);

        if ($this->userToSendEmail == UserNameEnum::PA_USER) {
            // TODO: Need to confirm from mirza
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
            $cc['advisoremail'] = $quote?->advisor?->email ?? '';
        }

        $cc = $this->applyCyberNotificationRules($quote, $cc);

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

        $response = app(CentralService::class)->sendAutomationEmail($quote, $emailData, $this->quoteTypeId, $this->workflowType);
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

    private function applyCyberNotificationRules($quote, array $cc): array
    {
        if ($this->quoteTypeId !== QuoteTypeId::Cyber) {
            return $cc;
        }

        $distribution = $this->getCyberDistributionEmails();
        $isBookingFailure = $this->processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;

        if ($isBookingFailure) {
            [$paEmail, $paName] = $this->getPaContactDetails();
            $this->recipientEmail = $paEmail;
            $this->recipientName = $paName;
        } elseif (! $this->recipientEmail && $quote?->advisor) {
            $this->recipientEmail = $quote->advisor->email;
            $this->recipientName = $quote->advisor->name;
        }

        if (! $this->recipientEmail) {
            [$paEmail, $paName] = $this->getPaContactDetails();
            $this->recipientEmail = $paEmail;
            $this->recipientName = $paName;
        }

        if ($quote?->advisor?->email) {
            $distribution[] = $quote->advisor->email;
        }

        $advisorManagerEmail = $this->getAdvisorManagerEmail($quote);
        if ($advisorManagerEmail) {
            $distribution[] = $advisorManagerEmail;
        }

        $distribution = array_unique(array_filter($distribution));
        if (! empty($distribution)) {
            $cc['cyberdistribution'] = implode(',', $distribution);
        }

        return $cc;
    }

    private function getCyberDistributionEmails(): array
    {
        $configured = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_FAILURE_EMAIL, useCache: true);

        if (empty($configured)) {
            return self::FALLBACK_CYBER_FAILURE_DISTRIBUTION;
        }

        $emails = array_map('trim', explode(',', $configured));
        $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

        return ! empty($emails) ? array_values($emails) : self::FALLBACK_CYBER_FAILURE_DISTRIBUTION;
    }

    private function getAdvisorManagerEmail($quote): ?string
    {
        if (! $quote?->advisor) {
            return null;
        }

        return $quote->advisor->managers()->first()?->email;
    }

    private function getPaContactDetails(): array
    {
        $paUser = User::activeUser()
            ->where('name', UserNameEnum::PA)
            ->first();

        if ($paUser && $paUser->email) {
            return [$paUser->email, $paUser->name ?? self::CYBER_BOOKING_TEAM_NAME];
        }

        return [self::CYBER_BOOKING_TEAM_EMAIL, self::CYBER_BOOKING_TEAM_NAME];
    }
}
