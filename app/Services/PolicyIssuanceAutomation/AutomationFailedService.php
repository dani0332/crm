<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\UserNameEnum;
use App\Services\Logger\LoggerService;

/**
 * Service class handling automation failure email logic.
 * Extracted from AutomationFailedJob to separate business logic from job infrastructure.
 */
class AutomationFailedService
{
    private ?string $insurerName = '';

    private $insuranceProvider;

    private string $appEnv;

    public function __construct()
    {
        $this->appEnv = config('constants.APP_ENV');
    }

    /**
     * Set the insurance provider and insurer name.
     */
    public function setInsuranceProvider($insuranceProvider): self
    {
        $this->insuranceProvider = $insuranceProvider;
        $this->insurerName = InsuranceProvidersEnum::getTextByCode($this->insuranceProvider?->code);

        return $this;
    }

    /**
     * Get the insurer name.
     */
    public function getInsurerName(): string
    {
        return $this->insurerName ?? '';
    }

    /**
     * Generate IMCRM link for the quote.
     */
    public function generateImcrmLink($quote, string $quoteType): string
    {
        $baseUrl = config('app.url', env('APP_URL'));
        return "{$baseUrl}/personal-quotes/{ucfirst($quoteType)}/{$quote->uuid}";
    }

    /**
     * Check if this is a Device LOB with NGI provider.
     */
    public function isDeviceNgiQuote(int $quoteTypeId, string $quoteType): bool
    {
        $isDeviceLob = $quoteTypeId === QuoteTypeId::Device || $quoteType === QuoteTypes::DEVICE->value;
        $isNgiProvider = $this->insuranceProvider?->code === InsuranceProvidersEnum::NGI;

        return $isDeviceLob && $isNgiProvider;
    }

    /**
     * Determine email recipient based on LOB/Provider.
     * FRD for Device/NGI:
     * - Booking Details API failure → Production Approval Team
     * - Other failures (Auto Capture, Policy Details, Document Upload) → Assigned SIC Advisor
     *
     * @return array{email: string, name: string}|null
     */
    public function determineRecipient($quote, bool $isDeviceNgi, string $processInvolved, ?string $userToSendEmail): ?array
    {
        // Device/NGI-specific recipient logic per FRD
        if ($isDeviceNgi) {
            $isBookingFailure = $processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;

            // Booking Details API failure goes to Production Approval Team
            if ($isBookingFailure) {
                $toEmail = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
                    ?: 'production.approval.team@insurancemarket.ae';

                LoggerService::info('AutomationFailedService - Device/NGI Booking failure, sending to Production Approval Team', extra: [
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

            LoggerService::info('AutomationFailedService - Device/NGI no advisor assigned, sending to fallback', extra: [
                'fallbackEmail' => $fallbackEmail,
            ]);

            return ['email' => $fallbackEmail, 'name' => 'Device Support Team'];
        }

        // Original logic for other LOBs (Car, etc.)
        if ($userToSendEmail == UserNameEnum::PA_USER) {
            $email = $quote?->kycDocumentUser?->createdBy?->email;
            $name = $quote?->kycDocumentUser?->createdBy?->name;

            if ($email && $name) {
                return ['email' => $email, 'name' => $name];
            }
        }

        if ($quote?->advisor) {
            return ['email' => $quote->advisor->email, 'name' => $quote->advisor->name];
        }

        LoggerService::info('AutomationFailedService - No advisor assigned, stopping job - Insurer: '.$this->insurerName);

        return null;
    }

    /**
     * Build CC emails with LOB/Provider-specific logic.
     * FRD for Device/NGI:
     * - Uses DEVICE_FAILURE_EMAIL_CC from ApplicationStorage
     * - Includes advisor in CC for booking failures (when TO is Production Approval Team)
     *
     * @return array{approvalemail: string|null, prodemail: string|null, advisoremail: string|null}
     */
    public function buildCcEmails($quote, bool $isDeviceNgi, string $processInvolved): array
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
            $isBookingFailure = $processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;
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
     * Build email data with LOB/Provider-specific fields.
     * Device/NGI: includes imcrmLink and escalationLink
     * Other LOBs: excludes imcrmLink and escalationLink
     */
    public function buildEmailData(
        $quote,
        string $quoteType,
        array $cc,
        bool $isDeviceNgi,
        string $actionRequired,
        string $recipientEmail,
        string $recipientName,
        string $statusAPIFailed,
        string $processInvolved,
        string $workflowType
    ): object {
        // Base email data common to all LOBs
        $emailData = [
            'actionRequired' => $actionRequired,
            'recipientEmail' => $recipientEmail,
            'recipientName' => $recipientName,
            'imcrmReferenceNumber' => $quote->code,
            'insurerApiStatus' => $statusAPIFailed,
            'insurerName' => $this->insuranceProvider?->text ?? '',
            'processInvolved' => $processInvolved,
            'cc' => $cc,
            'workflowType' => $workflowType,
        ];

        // Device/NGI-specific fields per FRD
        if ($isDeviceNgi) {
            $emailData['imcrmLink'] = $this->generateImcrmLink($quote, $quoteType);
            $emailData['escalationLink'] = $this->getEscalationLink();
        }

        return (object) $emailData;
    }

    /**
     * Get escalation link for Device/NGI from ApplicationStorage.
     */
    public function getEscalationLink(): string
    {
        $link = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_ESCALATION_LINK);

        return ! empty($link) ? $link : 'https://forms.clickup.com/2197982/f/232ey-57398/E5NVOINDYMZRFPTA3T';
    }
}
