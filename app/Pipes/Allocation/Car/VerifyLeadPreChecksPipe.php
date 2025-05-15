<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\CarRegistrationType;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class VerifyLeadPreChecksPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $isVerified = $this->verifyPreChecks();

        if (! $lead) {
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        // Check for SIC with Company Registration Type - should skip allocation
        if (isLeadSic($lead->uuid) && $lead->registration_type == CarRegistrationType::COMPANY) {
            LoggerService::info(self::class." - Lead is SIC and Registration type Company Webform. Skipping allocation for Ref-ID: {$lead->uuid}");
            $this->allocationRequest->markAsFailed();
            $this->throw('Lead is SIC and the registration type is Company. Skipping allocation.', self::OK);
        }

        $this->allocationRequest->setLead($lead);

        // Start the allocation process in the lead
        $lead->startAllocation();

        return $next($request);
    }

    private function verifyPreChecks(): bool
    {
        $lead = $this->lead;

        $continueAssignment = false;

        $isSICFlowEnabled = $lead->isSICFlowEnabled();
        $isAIG = $this->allocationRequest->isAIG();
        $isSICFlowDisabled = $lead->isSICFlowDisabled();

        if (! $this->allocationRequest->isOverrideAdvisorRequest() && ! empty($lead->advisor_id)) {
            LoggerService::info(self::class."::verifyPreChecks - Lead is already assigned to advisor with ID: {$lead->advisor_id}, skipping assignment");
        } elseif ($lead->isFakeOrDuplicate()) {
            LoggerService::info(self::class."::verifyPreChecks - Lead is fake or duplicate having quote_status_id {$lead->quote_status_id}, skipping assignment");
        } elseif ($lead->hasExemptedSource()) {
            LoggerService::info(self::class."::verifyPreChecks - Lead has exempted source {$lead->source}, skipping assignment");
        } elseif (($isSICFlowEnabled || $isAIG) && $lead->isRequestedAdvisorOrPaymentAuthorized()) {
            if ($isSICFlowEnabled) {
                LoggerService::info(self::class.'::verifyPreChecks - Lead has SIC flow enabled and either requested for an advisor or payment authorized, continuing assignment');
            } else {
                LoggerService::info(self::class.'::verifyPreChecks - Lead is AIG and either requested for an advisor or payment authorized, continuing assignment');
            }
            $continueAssignment = true;
        } elseif (($isSICFlowDisabled || ! $isAIG) && ! $lead->isRenewalUpload()) {
            if ($isSICFlowDisabled) {
                LoggerService::info(self::class.'::verifyPreChecks - Lead has SIC flow disabled and not Renewal Upload, continuing assignment');
            } elseif (! $isAIG) {
                LoggerService::info(self::class.'::verifyPreChecks - Lead is not AIG and not Renewal Upload, continuing assignment');
            } else {
                LoggerService::info(self::class.'::verifyPreChecks - Lead meets other criteria and not Renewal Upload, continuing assignment');
            }
            $continueAssignment = true;
        } elseif ($lead->isRenewalTierEmailSent()) {
            LoggerService::info(self::class.'::verifyPreChecks - Lead has renewal tier email sent, skipping assignment');
        } elseif ($lead->isRevivalRepliedOrPaid()) {
            LoggerService::info(self::class.'::verifyPreChecks - Lead is a Revival lead, continuing assignment');
            $continueAssignment = true;
        } elseif ($lead->isRenewalUpload()) {
            LoggerService::info(self::class.'::verifyPreChecks - Lead is Renewal Upload, skipping assignment');
        } else {
            LoggerService::info(self::class.'::verifyPreChecks - Lead does not meet any criteria, skipping assignment');
        }

        return $continueAssignment;
    }
}
