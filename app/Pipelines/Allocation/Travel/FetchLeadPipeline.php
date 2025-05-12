<?php

namespace App\Pipelines\Allocation\Travel;

use Closure;
use Illuminate\Support\Facades\Log;

class FetchLeadPipeline
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(array $data, Closure $next)
    {
        dd('FetchLeadPipeline', $data);
        // Process the request before sending it to the next pipeline

        // Example:
        // Log::info('Processing request through FetchLeadPipeline', ['data' => $passable]);

        // Send the processed request to the next pipeline
        return $next($passable);
    }

    private function verifyFetchLeadPreChecks(TravelQuote $travelQuote, ProcessTrackerService $tracker)
    {
        // Run Alliance Check only when the travel quote is a parent lead and the members are adult
        if (getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_ALLIANCE_TRAVEL_POLICY_ISSUANCE) == '1' && $travelQuote->isParent() && $travelQuote->isAdult()) {
            info(self::class.':verifyFetchLeadPreChecks - it is parent lead so checking for Alliance Travel Automation');
            // Check if the lead is associated with the ALNC provider
            $payment = PaymentRepository::mainQuotePayment($travelQuote);
            $insurer = getInsuranceProvider($payment, QuoteTypes::TRAVEL->value);
            $insurerCode = $insurer?->code;

            $isALNC = $insurerCode == InsuranceProvidersEnum::ALNC;

            $isALNC && info(self::class.":verifyFetchLeadPreChecks - it is Alliance so checking for automation status with insurer code: {$insurerCode} and payment code: {$payment?->code}");

            $isAutomationEnabled = (new PolicyIssuanceService)->init(self::TYPE, $insurerCode)?->isPolicyIssuanceAutomationEnabled();
            info(self::class." - verifyFetchLeadPreChecks: isALNC: {$isALNC} - isAutomationEnabled: {$isAutomationEnabled}");

            if ($isALNC && $isAutomationEnabled && $travelQuote->isSingleTrip() && $travelQuote->isPaid()) {
                $tracker->addStep(ProcessTrackerAllocationEnum::ALIANCE_PLAN_FOUND);
                if ($travelQuote->isAutomationCompleted() || $travelQuote->isBookingFailed()) {
                    $travelQuote->isAutomationCompleted() && $tracker->addStep(ProcessTrackerAllocationEnum::AUTOMATION_COMPLETED);
                    $travelQuote->isBookingFailed() && $tracker->addStep(ProcessTrackerAllocationEnum::BOOKING_FAILED);

                    $this->isCHSAdvisor = true;
                    $this->isMixEnquiryWithAutomation = $travelQuote->hasChild();
                } else {
                    if (! $travelQuote->isAutomationCompleted()) {
                        $tracker->addStep(ProcessTrackerAllocationEnum::AUTOMATION_NOT_COMPLETED);
                        info(self::class.':fetchLead - it is Alliance and automation is not yet completed so check fail cases');
                        if ($travelQuote->isPolicyIssuanceFailed()) {
                            $tracker->addStep(ProcessTrackerAllocationEnum::POLICY_ISSUANCE_FAILED);
                            info(self::class.':fetchLead - it is Alliance and automation is not yet completed but policy issuance failed so proceed with allocation');
                            $this->isSICAdvisor = true;
                            $this->isMixEnquiryWithAutomation = $travelQuote->hasChild();

                            return true;
                        }
                    }

                    return false;
                }
            }
        }

        return true;
    }

    public function fetchLead(ProcessTrackerService $tracker, $quoteId, $overrideAdvisorId = false)
    {
        $travelQuote = TravelQuote::where('uuid', $quoteId)->first();

        // Return null if no record is found
        if (! $travelQuote) {
            info(self::class.' : '.__FUNCTION__.' - Quote ID : '.$quoteId.' - lead not found.');

            return null;
        }

        info(self::class.'::fetchLead - Travel ILA', [
            'uuid' => $travelQuote->uuid,
            'payment_status_id' => $travelQuote->payment_status_id,
            'sic_advisor_requested' => $travelQuote->sic_advisor_requested,
            'quote_status_id' => $travelQuote->quote_status_id,
            'lead_allocation_failed_at' => $travelQuote->lead_allocation_failed_at,
            'sic_flow_enabled' => $travelQuote->sic_flow_enabled,
            'parent_quote_id' => $travelQuote->parent_id,
            'source' => $travelQuote->source,
        ]);

        if ($this->verifyFetchLeadPreChecks($travelQuote, $tracker) === false) {
            return null;
        }

        // allocate the lead if it's Alliance Provider, Automation is disabled for Alliance
        return TravelQuote::where('uuid', $quoteId)
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
                QuoteStatusEnum::Lost,
            ])
            ->when(! $overrideAdvisorId, fn ($q) => $q->whereNull('advisor_id'))
            ->where(function ($query) {
                $query->sicFlowDisabled()
                    ->orWhere(function ($subQuery) {
                        $subQuery->sicFlowEnabled()->requestedAdvisorOrPaymentAuthorized();
                    });
            })
            ->first();
    }
}
