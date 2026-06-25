<?php

namespace App\Pipes\Allocation\Travel;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Enums\quoteTypeCode;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Closure;

class VerifyLeadPreChecksPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $lead = $this->findLead();

        if (! $lead) {
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        $this->allocationRequest->setLead($lead);

        return $next($request);
    }

    private function findLead()
    {
        if ($this->verifyFetchLeadPreChecks() === false) {
            return null;
        }

        return $this->getLeadBaseQuery()
            ->where(function ($query) {
                $query->sicFlowDisabled()
                    ->orWhere(function ($subQuery) {
                        $subQuery->sicFlowEnabled()->advisorRequestedOrPaymentAuthorizedOrDeclined();
                    });
            })
            ->first();
    }

    private function verifyFetchLeadPreChecks()
    {
        $lead = $this->lead;

        if ($lead->isRenewalUpload()) {
            LoggerService::info(self::class.':verifyFetchLeadPreChecks - it is Renewal Upload so skipping allocation');

            return false;
        }

        $isQatarTravelPolicyIssuanceEnabled = getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_QATAR_TRAVEL_POLICY_ISSUANCE, useCache: true) == '1';
        $isDubaiTravelPolicyIssuanceEnabled = getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_DIC_TRAVEL_POLICY_ISSUANCE, useCache: true) == '1';

        // Run automation check only when the travel quote is a parent lead and the members are adult
        if (($isQatarTravelPolicyIssuanceEnabled || $isDubaiTravelPolicyIssuanceEnabled) && $lead->isParent() && $lead->isAdult()) {
            LoggerService::info(self::class.':verifyFetchLeadPreChecks - it is parent lead so checking for Travel Automation');
            $payment = PaymentRepository::mainQuotePayment($lead);
            $insurer = getInsuranceProvider($payment, $this->allocationRequest->getQuoteType()->value);
            $insurerCode = $insurer?->code;

            $isQIC = $insurerCode == InsuranceProviderEnum::QIC->value;
            $isDIC = $insurerCode == InsuranceProviderEnum::DIC->value;

            ($isQIC || $isDIC) && LoggerService::info(self::class.":verifyFetchLeadPreChecks - checking automation status with insurer code: {$insurerCode} and payment code: {$payment?->code}");

            $isAutomationEnabled = (new PolicyIssuanceService)->init(quoteTypeCode::Travel, $insurerCode)?->isPolicyIssuanceAutomationEnabled();
            LoggerService::info(self::class." - verifyFetchLeadPreChecks: isQIC: {$isQIC} - isDIC: {$isDIC} - isAutomationEnabled: {$isAutomationEnabled}");

            if (($isQIC || $isDIC) && $isAutomationEnabled && $lead->isSingleTrip() && $lead->isPaid()) {
                if ($lead->isAutomationCompleted() || $lead->isBookingFailed()) {
                    $this->allocationRequest->set('isCHSAdvisor', true);
                    $this->allocationRequest->set('isMixEnquiryWithAutomation', $lead->hasChild());
                } else {
                    if (! $lead->isAutomationCompleted()) {
                        LoggerService::info(self::class.':fetchLead - automation is not yet completed so check fail cases');
                        if ($lead->isPolicyIssuanceFailed()) {
                            LoggerService::info(self::class.':fetchLead - automation is not yet completed but policy issuance failed so proceed with allocation');
                            $this->allocationRequest->set('isSICAdvisor', true);
                            $this->allocationRequest->set('isMixEnquiryWithAutomation', $lead->hasChild());

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
