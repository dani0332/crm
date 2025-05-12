<?php

namespace App\Pipelines\Allocation\Travel;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
use App\Enums\quoteTypeCode;
use App\Models\TravelQuote;
use App\Pipelines\Allocation\Common\BaseAllocationPipeline;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Closure;

class FetchLeadPipeline extends BaseAllocationPipeline
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request, true);

        $lead = $this->resolveLead();

        if (! $lead) {
            $this->allocationRequest->getTracker()->saveResult(ProcessTrackerAllocationEnum::LEAD_NOT_FOUND, [
                '@statuses' => ['Fake', 'Duplicate', 'Lost'],
            ]);

            $this->throw('Lead not found or not under fetch criteria', self::NOT_FOUND);
        }

        $request->set('lead', $lead);

        return $next($request);
    }

    private function resolveLead()
    {
        $lead = $this->getBaseLead();

        if ($this->verifyFetchLeadPreChecks($lead) === false) {
            return null;
        }

        return $this->getLeadBaseQuery()
            ->where(function ($query) {
                $query->sicFlowDisabled()
                    ->orWhere(function ($subQuery) {
                        $subQuery->sicFlowEnabled()->requestedAdvisorOrPaymentAuthorized();
                    });
            })
            ->first();
    }

    private function verifyFetchLeadPreChecks(TravelQuote $lead)
    {
        $tracker = $this->allocationRequest->getTracker();
        $isAllianceTravelPolicyIssuanceEnabled = getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_ALLIANCE_TRAVEL_POLICY_ISSUANCE, useCache: true) == '1';

        // Run Alliance Check only when the travel quote is a parent lead and the members are adult
        if ($isAllianceTravelPolicyIssuanceEnabled && $lead->isParent() && $lead->isAdult()) {
            LoggerService::info(self::class.':verifyFetchLeadPreChecks - it is parent lead so checking for Alliance Travel Automation');
            // Check if the lead is associated with the ALNC provider
            $payment = PaymentRepository::mainQuotePayment($lead);
            $insurer = getInsuranceProvider($payment, $this->allocationRequest->getQuoteType()->value);
            $insurerCode = $insurer?->code;

            $isALNC = $insurerCode == InsuranceProvidersEnum::ALNC;

            $isALNC && LoggerService::info(self::class.":verifyFetchLeadPreChecks - it is Alliance so checking for automation status with insurer code: {$insurerCode} and payment code: {$payment?->code}");

            $isAutomationEnabled = (new PolicyIssuanceService)->init(quoteTypeCode::Travel, $insurerCode)?->isPolicyIssuanceAutomationEnabled();
            LoggerService::info(self::class." - verifyFetchLeadPreChecks: isALNC: {$isALNC} - isAutomationEnabled: {$isAutomationEnabled}");

            if ($isALNC && $isAutomationEnabled && $lead->isSingleTrip() && $lead->isPaid()) {
                $tracker->addStep(ProcessTrackerAllocationEnum::ALIANCE_PLAN_FOUND);
                if ($lead->isAutomationCompleted() || $lead->isBookingFailed()) {
                    $lead->isAutomationCompleted() && $tracker->addStep(ProcessTrackerAllocationEnum::AUTOMATION_COMPLETED);
                    $lead->isBookingFailed() && $tracker->addStep(ProcessTrackerAllocationEnum::BOOKING_FAILED);

                    $this->allocationRequest->set('isCHSAdvisor', true);
                    $this->allocationRequest->set('isMixEnquiryWithAutomation', $lead->hasChild());
                } else {
                    if (! $lead->isAutomationCompleted()) {
                        $tracker->addStep(ProcessTrackerAllocationEnum::AUTOMATION_NOT_COMPLETED);
                        LoggerService::info(self::class.':fetchLead - it is Alliance and automation is not yet completed so check fail cases');
                        if ($lead->isPolicyIssuanceFailed()) {
                            $tracker->addStep(ProcessTrackerAllocationEnum::POLICY_ISSUANCE_FAILED);
                            LoggerService::info(self::class.':fetchLead - it is Alliance and automation is not yet completed but policy issuance failed so proceed with allocation');
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
