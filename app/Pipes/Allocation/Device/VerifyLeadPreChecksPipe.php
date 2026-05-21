<?php

namespace App\Pipes\Allocation\Device;

use App\Enums\LeadSourceEnum;
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
        LoggerService::info(self::class.' - Starting pre-checks for Device lead');

        $this->setRequest($request);
        $isVerified = $this->verifyFetchLeadPreChecks();
        if (! $isVerified) {
            $isVerified = $this->verifyPreChecks();
        }
        if (! $isVerified) {
            LoggerService::info(self::class.' - Device lead does not meet pre-check criteria');
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        } else {
            LoggerService::info(self::class.' - Device lead pre-checks passed', extra: [
                'leadUuid' => $this->lead->uuid,
                'leadId' => $this->lead->id,
            ]);

            return $next($request);
        }

    }

    private function verifyPreChecks(): bool
    {
        $lead = $this->lead;

        if (! $lead) {
            LoggerService::info(self::class.' - Lead not found, skipping assignment');

            return false;
        }

        // Load deviceQuote relation if not already loaded
        if (! $lead->relationLoaded('deviceQuote')) {
            $lead->load('deviceQuote');
        }

        // Check if lead is SIC and advisor is requested
        $isSIC = $lead->isSIC(QuoteTypes::DEVICE);

        $isAdvisorRequested = $lead->deviceQuote && isset($lead->deviceQuote->sic_advisor_requested) && (bool) $lead->deviceQuote->sic_advisor_requested;
        $isCHSAdvisor = $this->allocationRequest->get('isCHSAdvisor', false);

        $continueAssignment = false;

        // Check base conditions first
        if ($lead->source === LeadSourceEnum::EA_IMCRM && $lead->ea_model === 'collaborate' && empty($lead->expert_advisor_id)) {
            LoggerService::info(self::class.' - EA collaborate lead without expert advisor, proceeding with expert advisor allocation');
            $continueAssignment = true;
        } elseif (! $this->allocationRequest->isOverrideAdvisorRequest() && ! empty($lead->advisor_id)) {
            LoggerService::info(self::class.' - Lead is already assigned to advisor with ID: '.$lead->advisor_id.', skipping assignment');
        } elseif ($lead->isFakeOrDuplicate()) {
            LoggerService::info(self::class.' - Lead is fake or duplicate having quote_status_id '.$lead->quote_status_id.', skipping assignment');
        } elseif ($lead->isPaid()) {
            LoggerService::info(self::class.' - Lead is paid, continuing assignment');
            $continueAssignment = true;
        } elseif ($isSIC && $isAdvisorRequested) {
            LoggerService::info(self::class.' - Lead is SIC and advisor is requested, continuing assignment');
            $continueAssignment = true;
        } elseif ($lead->isPaymentAuthorized() || $lead->isPaymentAuthorizedOrDeclined()) {
            LoggerService::info(self::class.' - Lead is Payment Authorized or Payment Authorized or Declined, continuing assignment');
            $continueAssignment = true;
        } elseif ($lead->hasRemainedUnauthorizedFor12Hours()) {
            LoggerService::info(self::class.' - Lead has remained unauthorized for 12 hours, continuing assignment');
            $continueAssignment = true;
        } else {
            LoggerService::info(self::class.' - Lead does not meet allocation criteria (SIC and advisor requested), skipping assignment', extra: [
                'isSIC' => $isSIC,
                'isAdvisorRequested' => $isAdvisorRequested,
            ]);
        }

        return $continueAssignment;
    }

    private function verifyFetchLeadPreChecks(): bool
    {
        $lead = $this->lead;

        if (! $lead) {
            LoggerService::info(self::class.' - Lead not found, skipping assignment');

            return false;
        }

        $payment = PaymentRepository::mainQuotePayment($lead);
        $insurer = getInsuranceProvider($payment, $this->allocationRequest->getQuoteType()->value);
        $insurerCode = $insurer?->code;

        $isAutomationEnabled = (new PolicyIssuanceService)
            ->init(QuoteTypes::DEVICE->value, $insurerCode)
            ?->isPolicyIssuanceAutomationEnabled() ?? false;

        LoggerService::info(self::class." - verifyFetchLeadPreChecks: insurerCode: {$insurerCode} - isAutomationEnabled: {$isAutomationEnabled} - isPaid: {$lead->isPaid()}");
        if ($isAutomationEnabled && $lead->isPaid()) {
            return $this->handleAutomationStatus();
        }

        return $isAutomationEnabled;
    }

    private function handleAutomationStatus(): bool
    {
        $allowAllocation = false;

        if ($this->lead->isAutomationCompleted()) {
            $this->allocationRequest->set('isCHSAdvisor', true);
            LoggerService::info(self::class.':fetchLead - it is Device and automation is completed so proceed with allocation');
            $allowAllocation = true;
        } else {
            LoggerService::info(self::class.':fetchLead - it is Device and automation is not yet completed, skipping allocation');
        }

        return $allowAllocation;
    }
}
