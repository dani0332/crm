<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\UserNameEnum;
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

        // Check if this is a Device/NGI quote for FRD-specific logic
        $isDeviceNgi = $this->isDeviceNgiQuote($quoteType);

        // Determine recipient based on LOB/Provider
        $recipient = $this->determineRecipient($quote, $isDeviceNgi);
        if (! $recipient) {
            return;
        }
        $this->recipientEmail = $recipient['email'];
        $this->recipientName = $recipient['name'];

        // Build CC emails with LOB/Provider-specific logic
        $cc = $this->buildCcEmails($quote, $isDeviceNgi);

        // Build email data with LOB-specific fields
        $emailData = $this->buildEmailData($quote, $quoteType, $cc, $isDeviceNgi);

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

    private function generateImcrmLink($quote, $quoteType)
    {
        $baseUrl = config('app.url', env('APP_URL'));

        return "{$baseUrl}/personal-quotes/{$quoteType}/{$quote->uuid}";
    }

    /**
     * Check if this is a Device LOB with NGI provider
     */
    private function isDeviceNgiQuote(string $quoteType): bool
    {
        $isDeviceLob = $this->quoteTypeId === QuoteTypeId::Device || $quoteType === QuoteTypes::DEVICE->value;
        $isNgiProvider = $this->insuranceProvider?->code === InsuranceProvidersEnum::NGI;

        return $isDeviceLob && $isNgiProvider;
    }

    /**
     * Determine email recipient based on LOB/Provider
     * FRD for Device/NGI:
     * - Booking Details API failure → Production Approval Team
     * - Other failures (Auto Capture, Policy Details, Document Upload) → Assigned SIC Advisor
     *
     * @return array{email: string, name: string}|null
     */
    private function determineRecipient($quote, bool $isDeviceNgi): ?array
    {
        // Device/NGI-specific recipient logic per FRD
        if ($isDeviceNgi) {
            $isBookingFailure = $this->processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;

            // Booking Details API failure goes to Production Approval Team
            if ($isBookingFailure) {
                $toEmail = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
                    ?: 'production.approval.team@insurancemarket.ae';

                LoggerService::info('job:AutomationFailedJob - Device/NGI Booking failure, sending to Production Approval Team', extra: [
                    'toEmail' => $toEmail,
                ]);

                return ['email' => $toEmail, 'name' => 'Production Approval Team'];
            }

            // Other failures go to assigned SIC advisor
            if ($quote?->advisor) {
                return ['email' => $quote->advisor->email, 'name' => $quote->advisor->name];
            }

            // Fallback for Device/NGI when no advisor - don't stop, send to PA Team
            $fallbackEmail = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
                ?: 'production.approval.team@insurancemarket.ae';

            LoggerService::info('job:AutomationFailedJob - Device/NGI no advisor assigned, sending to fallback', extra: [
                'fallbackEmail' => $fallbackEmail,
            ]);

            return ['email' => $fallbackEmail, 'name' => 'Device Support Team'];
        }

        // Original logic for other LOBs (Car, etc.)
        if ($this->userToSendEmail == UserNameEnum::PA_USER) {
            $email = $quote?->kycDocumentUser?->createdBy?->email;
            $name = $quote?->kycDocumentUser?->createdBy?->name;

            if ($email && $name) {
                return ['email' => $email, 'name' => $name];
            }
        }

        if ($quote?->advisor) {
            return ['email' => $quote->advisor->email, 'name' => $quote->advisor->name];
        }

        LoggerService::info('job:AutomationFailedJob - No advisor assigned, stopping job - Insurer: '.$this->insurerName);

        return null;
    }

    /**
     * Build CC emails with LOB/Provider-specific logic
     * FRD for Device/NGI:
     * - Uses DEVICE_FAILURE_EMAIL_CC from ApplicationStorage
     * - Includes advisor in CC for booking failures (when TO is Production Approval Team)
     *
     * @return array{approvalemail: string|null, prodemail: string|null, advisoremail: string|null}
     */
    private function buildCcEmails($quote, bool $isDeviceNgi): array
    {
        $cc = [
            'approvalemail' => null,
            'prodemail' => null,
            'advisoremail' => null,
        ];

        if (! in_array($this->appEnv, [EnvEnum::PRODUCTION, EnvEnum::STAGING])) {
            return $cc;
        }

        if ($isDeviceNgi) {
            // Device/NGI-specific CC per FRD
            $cc['approvalemail'] = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC);
            $cc['prodemail'] = getAppStorageValueByKey(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL);

            // FRD: Include advisor in CC for booking failures (when email goes to PA Team)
            $isBookingFailure = $this->processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;
            if ($isBookingFailure && $quote?->advisor) {
                $cc['advisoremail'] = $quote->advisor->email;
            }
        } else {
            // Original logic for other LOBs
            $cc['approvalemail'] = getAppStorageValueByKey(ApplicationStorageEnums::APPROVAL_PRODUCTION_EMAIL);
            $cc['prodemail'] = getAppStorageValueByKey(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL);
            $cc['advisoremail'] = $quote?->advisor?->email ?? '';
        }

        return $cc;
    }

    /**
     * Build email data with LOB/Provider-specific fields
     * Device/NGI: includes imcrmLink and escalationLink
     * Other LOBs: excludes imcrmLink and escalationLink
     */
    private function buildEmailData($quote, string $quoteType, array $cc, bool $isDeviceNgi): object
    {
        // Base email data common to all LOBs
        $emailData = [
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

        // Device/NGI-specific fields per FRD
        if ($isDeviceNgi) {
            $emailData['imcrmLink'] = $this->generateImcrmLink($quote, $quoteType);
            $emailData['escalationLink'] = $this->getEscalationLink();
        }

        return (object) $emailData;
    }

    /**
     * Get escalation link for Device/NGI from ApplicationStorage
     */
    private function getEscalationLink(): string
    {
        $link = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_ESCALATION_LINK);

        return ! empty($link) ? $link : 'https://forms.clickup.com/2197982/f/232ey-57398/E5NVOINDYMZRFPTA3T';
    }
}
