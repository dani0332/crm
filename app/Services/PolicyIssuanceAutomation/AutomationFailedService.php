<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation;

use App\DTO\AutomationFailedEmailDataRequest;
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
        return "{$baseUrl}/personal-quotes/" . ucfirst($quoteType) . "/{$quote->uuid}";
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
        if ($isDeviceNgi) {
            return $this->determineDeviceNgiRecipient($quote, $processInvolved);
        }

        return $this->determineOtherLobRecipient($quote, $userToSendEmail);
    }

    /**
     * Determine recipient for Device/NGI quotes.
     * FRD: Booking Details API failure → Production Approval Team
     * Other failures → Assigned SIC Advisor (or fallback to PA Team)
     *
     * @return array{email: string, name: string}|null
     */
    private function determineDeviceNgiRecipient($quote, string $processInvolved): ?array
    {
        $isBookingFailure = $processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;

        if ($isBookingFailure) {
            return $this->getProductionApprovalTeamRecipient();
        }

        if ($quote?->advisor) {
            return ['email' => $quote->advisor->email, 'name' => $quote->advisor->name];
        }

        return $this->getDeviceNgiFallbackRecipient();
    }

    /**
     * Determine recipient for other LOBs (Car, etc.).
     *
     * @return array{email: string, name: string}|null
     */
    private function determineOtherLobRecipient($quote, ?string $userToSendEmail): ?array
    {
        if ($userToSendEmail == UserNameEnum::PA_USER) {
            $recipient = $this->getPaUserRecipient($quote);
            if ($recipient) {
                return $recipient;
            }
        }

        if ($quote?->advisor) {
            return ['email' => $quote->advisor->email, 'name' => $quote->advisor->name];
        }

        LoggerService::info('AutomationFailedService - No advisor assigned, stopping job - Insurer: '.$this->insurerName);

        return null;
    }

    /**
     * Get Production Approval Team recipient for Device/NGI booking failures.
     *
     * @return array{email: string, name: string}
     */
    private function getProductionApprovalTeamRecipient(): array
    {
        $toEmail = $this->getDeviceFailureEmailTo();

        LoggerService::info('AutomationFailedService - Device/NGI Booking failure, sending to Production Approval Team', extra: [
            'toEmail' => $toEmail,
        ]);

        return ['email' => $toEmail, 'name' => 'Production Approval Team'];
    }

    /**
     * Get fallback recipient for Device/NGI when no advisor is assigned.
     *
     * @return array{email: string, name: string}
     */
    private function getDeviceNgiFallbackRecipient(): array
    {
        $fallbackEmail = $this->getDeviceFailureEmailTo();

        LoggerService::info('AutomationFailedService - Device/NGI no advisor assigned, sending to fallback', extra: [
            'fallbackEmail' => $fallbackEmail,
        ]);

        return ['email' => $fallbackEmail, 'name' => 'Device Support Team'];
    }

    /**
     * Get PA user recipient from quote's KYC document user.
     *
     * @return array{email: string, name: string}|null
     */
    private function getPaUserRecipient($quote): ?array
    {
        $email = $quote?->kycDocumentUser?->createdBy?->email;
        $name = $quote?->kycDocumentUser?->createdBy?->name;

        if ($email && $name) {
            return ['email' => $email, 'name' => $name];
        }

        return null;
    }

    /**
     * Get Device failure email from ApplicationStorage with fallback.
     *
     * @return string
     */
    private function getDeviceFailureEmailTo(): string
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
            ?: 'production.approval.team@insurancemarket.ae';
    }

    /**
     * Build CC emails with LOB/Provider-specific logic.
     * FRD for Device/NGI:
     * - Uses DEVICE_FAILURE_EMAIL_CC from ApplicationStorage
     * - Includes advisor in CC for booking failures (when TO is Production Approval Team)
     *
     * @return array{approvalemail: string|null, prodemail: string|null, advisoremail: string|null, ccEmails?: array}
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
            return $this->buildDeviceCcPayload($quote, $processInvolved);
        } else {
            // Original logic for other LOBs
            $cc['approvalemail'] = getAppStorageValueByKey(ApplicationStorageEnums::APPROVAL_PRODUCTION_EMAIL);
            $cc['prodemail'] = getAppStorageValueByKey(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL);
            $cc['advisoremail'] = $quote?->advisor?->email ?? '';
        }

        return $cc;
    }

    /**
     * Assemble Device/NGI CC payload with normalized email list.
     *
     * @return array{approvalemail: string|null, prodemail: string|null, advisoremail: string|null, ccEmails: array}
     */
    private function buildDeviceCcPayload($quote, string $processInvolved): array
    {
        $ccEmails = $this->parseCommaSeparatedEmails(
            getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC)
        );

        $payload = [
            'approvalemail' => ! empty($ccEmails) ? implode(',', $ccEmails) : null,
            'prodemail' => getAppStorageValueByKey(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL) ?: null,
            'advisoremail' => null,
            'ccEmails' => $ccEmails,
        ];

        if ($processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY && $quote?->advisor?->email) {
            $payload['advisoremail'] = $quote->advisor->email;
        }

        return $payload;
    }

    /**
     * Split and clean comma separated emails.
     *
     * @return string[]
     */
    private function parseCommaSeparatedEmails(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $rawEmails = preg_split('/[,\s]+/', trim($value));

        return array_values(array_filter($rawEmails, static fn ($email) => ! empty($email)));
    }

    /**
     * Build email data with LOB/Provider-specific fields.
     * Device/NGI: includes imcrmLink and escalationLink (Device-specific)
     * Other LOBs: includes imcrmLink, escalationLink, refId, and ccEmails (Cyber-specific)
     *
     * @param AutomationFailedEmailDataRequest $request Contains email recipient, CC, process, and workflow data
     */
    public function buildEmailData(
        $quote,
        AutomationFailedEmailDataRequest $request
    ): object {
        // Base email data common to all LOBs
        $emailData = [
            'actionRequired' => $request->actionRequired,
            'recipientEmail' => $request->recipientEmail,
            'recipientName' => $request->recipientName,
            'imcrmReferenceNumber' => $quote->code,
            'insurerApiStatus' => $request->statusAPIFailed,
            'insurerName' => $this->insuranceProvider?->text ?? '',
            'processInvolved' => $request->processInvolved,
            'cc' => $request->cc,
            'workflowType' => $request->workflowType,
            'imcrmLink' => $quote->getCrmQuoteLink(),
        ];

        // Device/NGI-specific fields per FRD
        if ($request->isDeviceNgi) {
            $emailData['escalationLink'] = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_ESCALATION_LINK);
            $emailData['triggerPoint'] = $this->getDeviceFailureTriggerPoint($request->processInvolved);
            $emailData['replyTo'] = $this->getDeviceFailureEmailReplyTo();
        }

        return (object) $emailData;
    }

    private function getDeviceFailureEmailReplyTo(): string
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_REPLY_TO)
            ?: 'production.approval.team@insurancemarket.ae';
    }

    private function getDeviceFailureTriggerPoint(string $processInvolved): string
    {
        return match ($processInvolved) {
            PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY => PolicyIssuanceEnum::BOOKING_DETAILS_API_ACTION_MESSAGE,
            PolicyIssuanceEnum::PROCESS_INVOLVED_PAYMENT_CAPTURE => PolicyIssuanceEnum::AUTO_CAPTURE_ACTION_MESSAGE,
            PolicyIssuanceEnum::PROCESS_INVOLVED_ISSUE_POLICY => PolicyIssuanceEnum::POLICY_DETAIL_API_ACTION_MESSAGE,
            PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_DOCUMENTS,
            PolicyIssuanceEnum::PROCESS_INVOLVED_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM => PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_ACTION_MESSAGE,
            default => $processInvolved,
        };
    }

}
