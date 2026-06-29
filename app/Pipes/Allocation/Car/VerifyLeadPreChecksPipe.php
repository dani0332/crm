<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\QuoteTypes;
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
        } elseif ($lead->isRevivalCommsIntentHighOrMedium()) {
            LoggerService::info(self::class.'::verifyPreChecks - Lead is a Revival lead and has intent high or medium, continuing assignment');
            $continueAssignment = true;
        } elseif ($lead->isRevivalReinstated()) {
            if (($isSICFlowEnabled || $isAIG) && ! $lead->isAdvisorRequestedOrAuthorizedOrRequestedLinkOrDeclined()) {
                LoggerService::info(self::class.'::verifyPreChecks - REVIVAL_REINSTATED lead is SIC/AIG without advisor/payment trigger, skipping assignment');
            } else {
                LoggerService::info(self::class.'::verifyPreChecks - REVIVAL_REINSTATED lead passed flow checks, continuing assignment');
                $continueAssignment = true;
            }
        } elseif ($lead->isLeadSourceCar24()) {
            if ($lead->isPaymentAuthorized()) {
                LoggerService::info(self::class.'::verifyPreChecks - Lead source is Cars24 and payment is authorized, continuing assignment');
                $continueAssignment = true;
            } else {
                LoggerService::info(self::class.'::verifyPreChecks - Lead source is Cars24 but payment is not authorized, skipping assignment');
            }
        } elseif ($lead->hasExemptedSource()) {
            if ($lead->isCatABuyLeadApplicable(QuoteTypes::CAR_CAT_A)) {
                LoggerService::info(self::class.'::verifyPreChecks - Lead is a Revival lead and is a CAT A nationality, continuing assignment');
                $continueAssignment = true;
            } else {
                LoggerService::info(self::class."::verifyPreChecks - Lead has exempted source {$lead->source}, skipping assignment");
            }
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
            LoggerService::info(self::class.'::verifyPreChecks - Lead has renewal tier email sent, so checking if it has car value', extra: [
                'car_value' => $lead->car_value,
                'has_car_value' => $lead->hasCarValue(),
            ]);
            if ($lead->hasCarValue()) {
                LoggerService::info(self::class.'::verifyPreChecks - Lead has renewal tier email sent and has car value, continuing assignment');
                $continueAssignment = true;
            } else {
                LoggerService::info(self::class.'::verifyPreChecks - Lead has renewal tier email sent but no car value, skipping assignment');
            }
        } elseif ($lead->isRevivalRepliedOrPaid()) {
            LoggerService::info(self::class.'::verifyPreChecks - Lead is a Revival lead and is a CAT A nationality, continuing assignment');
            $continueAssignment = true;
        } elseif ($lead->isRenewalUpload()) {
            LoggerService::info(self::class.'::verifyPreChecks - Lead is Renewal Upload, skipping assignment');
        } else {
            LoggerService::info(self::class.'::verifyPreChecks - Lead does not meet any criteria, skipping assignment');
        }

        return $continueAssignment;
    }
}
