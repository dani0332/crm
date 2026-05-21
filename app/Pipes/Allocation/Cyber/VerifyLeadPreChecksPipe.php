<?php

namespace App\Pipes\Allocation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Closure;

class VerifyLeadPreChecksPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting pre-checks for Cyber lead');

        $this->setRequest($request);

        if ($this->verifyFetchLeadPreChecks() === false) {
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        $isVerified = $this->verifyPreChecks();

        if (! $isVerified) {
            LoggerService::info(self::class.' - Cyber lead does not meet pre-check criteria');
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        LoggerService::info(self::class.' - Cyber lead pre-checks passed', extra: [
            'leadUuid' => $this->lead->uuid,
            'leadId' => $this->lead->id,
        ]);

        return $next($request);
    }

    private function verifyPreChecks(): bool
    {
        $lead = $this->lead;

        if (! $lead) {
            LoggerService::info(self::class.' - Lead not found, skipping assignment');

            return false;
        }

        // Load cyberQuote relation if not already loaded
        if (! $lead->relationLoaded('cyberQuote')) {
            $lead->load('cyberQuote');
        }

        // Check if lead is SIC and advisor is requested
        $isSIC = $lead->isSIC(QuoteTypes::CYBER);
        $isAdvisorRequested = $lead->cyberQuote && isset($lead->cyberQuote->sic_advisor_requested) && (bool) $lead->cyberQuote->sic_advisor_requested;
        $isCHSAdvisor = $this->allocationRequest->get('isCHSAdvisor', false);

        $continueAssignment = false;

        // Check base conditions first
        if ($lead->source === LeadSourceEnum::EA_IMCRM && $lead->ea_model === 'collaborate' && empty($lead->expert_advisor_id)) {
            LoggerService::info(self::class.' - EA collaborate lead without expert advisor, proceeding with expert advisor allocation');
            $continueAssignment = true;
        } elseif (! $this->allocationRequest->isOverrideAdvisorRequest() && ! empty($lead->advisor_id) && ! $isCHSAdvisor) {
            LoggerService::info(self::class.' - Lead is already assigned to advisor with ID: '.$lead->advisor_id.', skipping assignment');
        } elseif ($lead->isFakeOrDuplicate()) {
            LoggerService::info(self::class.' - Lead is fake or duplicate having quote_status_id '.$lead->quote_status_id.', skipping assignment');
        } elseif ($isCHSAdvisor) {
            LoggerService::info(self::class.' - CHS advisor assignment requested (AWNI automation), continuing assignment');
            $continueAssignment = true;
        } elseif ($lead->isPaid()) {
            // for cyber we do not assign if the lead is paid, as it was requested by business
            // Exception: payment authorized 24h ago with no documents - assign advisor anyway
            if ($lead->hasPaymentAuthorizedWithNoDocuments()) {
                LoggerService::info(self::class.' - Lead is paid but has payment authorized 24h ago with no documents, continuing assignment');
                $continueAssignment = true;
            } else {
                LoggerService::info(self::class.' - Lead is paid, skipping assignment');
            }
        } elseif ($isSIC && $isAdvisorRequested) {
            LoggerService::info(self::class.' - Lead is SIC and advisor is requested, continuing assignment');
            $continueAssignment = true;
        } else {
            LoggerService::info(self::class.' - Lead does not meet allocation criteria (SIC and advisor requested), skipping assignment', extra: [
                'isSIC' => $isSIC,
                'isAdvisorRequested' => $isAdvisorRequested,
                'isCHSAdvisor' => $isCHSAdvisor,
            ]);
        }

        return $continueAssignment;
    }

    private function verifyFetchLeadPreChecks()
    {
        $lead = $this->lead;

        if (! $lead) {
            return false;
        }

        $isAwniCyberPolicyIssuanceEnabled = getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_AWNI_CYBER_POLICY_ISSUANCE, useCache: true) == '1';

        if ($isAwniCyberPolicyIssuanceEnabled) {
            LoggerService::info(self::class.':verifyFetchLeadPreChecks - checking for AWNI Cyber Automation');
            $payment = PaymentRepository::mainQuotePayment($lead);
            $insurer = getInsuranceProvider($payment, $this->allocationRequest->getQuoteType()->value);
            $insurerCode = $insurer?->code;

            $isAWNI = $insurerCode == InsuranceProviderEnum::AWNI->value;

            $isAWNI && LoggerService::info(self::class.":verifyFetchLeadPreChecks - it is AWNI so checking for automation status with insurer code: {$insurerCode} and payment code: {$payment?->code}");

            $isAutomationEnabled = (new PolicyIssuanceService)->init(quoteTypeCode::CYBER, $insurerCode)?->isPolicyIssuanceAutomationEnabled();
            LoggerService::info(self::class." - verifyFetchLeadPreChecks: isAWNI: {$isAWNI} - isAutomationEnabled: {$isAutomationEnabled}");

            if ($isAWNI && $isAutomationEnabled && $lead->isPaid()) {
                return $this->handleAwniAutomationStatus();
            }
        }

        return true;
    }

    private function handleAwniAutomationStatus(): bool
    {
        $allowAllocation = false;

        if ($this->lead->isAutomationCompleted()) {
            $this->allocationRequest->set('isCHSAdvisor', true);
            LoggerService::info(self::class.':fetchLead - it is AWNI and automation is completed so proceed with allocation');
            $allowAllocation = true;
        } elseif ($this->lead->hasPaymentAuthorizedWithNoDocuments()) {
            // Do NOT set isCHSAdvisor - it will assign to normal advisor, not to Hapex User
            LoggerService::info(self::class.':fetchLead - it is AWNI and automation not completed, but lead has payment authorized 24h ago with no documents, proceeding with normal advisor allocation');
            $allowAllocation = true;
        } else {
            LoggerService::info(self::class.':fetchLead - it is AWNI and automation is not yet completed, skipping allocation');
        }

        return $allowAllocation;
    }
}
