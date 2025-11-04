<?php

namespace App\Pipes\Allocation\Car;

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

        // If this is a tier-evaluation-only request, skip pre-checks entirely.
        if ($this->allocationRequest->isEvaluateTierOnlyRequest()) {
            LoggerService::info(self::class.'::handle - EvaluateTierOnly request detected, skipping pre-checks');

            return $next($request);
        }

        $isVerified = $this->verifyPreChecks();

        if (! $isVerified) {
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

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
        } elseif (($isSICFlowEnabled || $isAIG) && $lead->isAdvisorRequestedOrAuthorizedOrRequestedLinkOrDeclined()) {
            if ($isSICFlowEnabled) {
                LoggerService::info(self::class.'::verifyPreChecks - Lead has SIC flow enabled and either requested for an advisor or payment authorized or link requested or declined or failed, continuing assignment');
            } else {
                LoggerService::info(self::class.'::verifyPreChecks - Lead is AIG and either requested for an advisor or payment authorized or link requested or declined or failed, continuing assignment');
            }
            $continueAssignment = true;
        } elseif ($isSICFlowDisabled && ! $isAIG && ! $lead->isRenewalUpload()) {
            LoggerService::info(self::class.'::verifyPreChecks - Lead has SIC flow disabled, is not AIG, and not Renewal Upload, continuing assignment');
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
