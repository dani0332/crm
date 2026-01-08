<?php

namespace App\Pipes\Allocation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypes;
use App\Enums\quoteTypeCode;
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

        $lead = $this->findLead();

        if (! $lead) {
            LoggerService::info(self::class.' - Cyber lead does not meet pre-check criteria or not found');
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        LoggerService::info(self::class.' - Cyber lead pre-checks passed', extra: [
            'leadUuid' => $lead->uuid,
            'leadId' => $lead->id,
        ]);

        $this->allocationRequest->setLead($lead);

        return $next($request);
    }

    private function findLead()
    {
        if ($this->verifyFetchLeadPreChecks() === false) {
            return null;
        }

        LoggerService::info(self::class.' - Fetching Cyber lead');

        $lead = $this->getLeadBaseQuery()
            ->with('cyberQuote')
            ->where(function ($query) {
                $this->verifyPreChecks($query);
            })
            ->first();

        if (! $lead) {
            LoggerService::info(self::class.' - Cyber lead not found');

            return null;
        }

        LoggerService::info(self::class.' - Cyber lead found successfully', extra: [
            'leadUuid' => $lead->uuid,
            'leadId' => $lead->id,
            'hasAdvisor' => $lead->advisor_id ? true : false,
            'hasCyberQuote' => $lead->cyberQuote ? true : false,
        ]);

        return $lead;
    }

    private function verifyPreChecks($query)
    {
        $query->where(function ($q) {
            // Check if lead is SIC and advisor is requested
            $q->isSIC(QuoteTypes::CYBER)
                ->whereHas('cyberQuote', function ($cyberQuery) {
                    $cyberQuery->where('sic_advisor_requested', 1);
                });
        });
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
                if ($lead->isAutomationCompleted() || $lead->isBookingFailed()) {
                    $this->allocationRequest->set('isCHSAdvisor', true);
                    LoggerService::info(self::class.':fetchLead - it is AWNI and automation is completed or booking failed so proceed with allocation');
                } else {
                    if (! $lead->isAutomationCompleted()) {
                        LoggerService::info(self::class.':fetchLead - it is AWNI and automation is not yet completed so check fail cases');
                        if ($lead->isPolicyIssuanceFailed()) {
                            LoggerService::info(self::class.':fetchLead - it is AWNI and automation is not yet completed but policy issuance failed so proceed with allocation');

                            return true;
                        }
                    }

                    return false;
                }
            }
        }

        return true;
    }
}
