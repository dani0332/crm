<?php

namespace App\Pipes\Allocation\Device;

use App\Enums\LeadSourceEnum;
use App\Models\PersonalQuote;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class EvaluateTeamPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting team evaluation for Device lead');

        $this->setRequest($request);

        $lead = $this->allocationRequest->getLead();

        $teamId = $this->evaluateTeamId($lead);

        $this->allocationRequest->setTeamId($teamId);

        LoggerService::info(self::class.' - Team evaluation completed for Device lead', extra: [
            'teamId' => $teamId,
            'willUseTeam' => $teamId ? true : false,
        ]);

        return $next($request);
    }

    private function evaluateTeamId(PersonalQuote $lead)
    {
        $defaultTeamId = false;

        $isPaymentAuthorizedOrDeclined = $lead->isPaymentAuthorizedOrDeclined();
        $hasRetryFlag = $lead->isAllocationFailed();

        $deviceQuote = $lead->deviceQuote;
        $sicAdvisorRequested = true;

        if ($deviceQuote && isset($deviceQuote->sic_advisor_requested)) {
            $sicAdvisorRequested = (bool) $deviceQuote->sic_advisor_requested;
        }

        LoggerService::info(self::class.' - Device lead conditions evaluation', extra: [
            'isPaymentAuthorizedOrDeclined' => $isPaymentAuthorizedOrDeclined,
            'sicAdvisorRequested' => $sicAdvisorRequested,
            'deviceQuoteExists' => $deviceQuote ? true : false,
            'hasRetryFlag' => $hasRetryFlag,
        ]);

        // EA_IMCRM leads bypass payment/SIC checks — proceed directly to advisor allocation
        if ($lead->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.' - EA_IMCRM lead detected, bypassing SIC/payment checks', extra: [
                'uuid' => $lead->uuid,
                'ea_model' => $lead->ea_model?->value,
            ]);

            return $defaultTeamId;
        }

        // SIC advisor requested or has retry flag, assign to hardcoded advisors
        if ($sicAdvisorRequested || $hasRetryFlag || $lead->isPaymentAuthorized() || $isPaymentAuthorizedOrDeclined || $lead->hasRemainedUnauthorizedFor12Hours()) {
            $reason = '';
            if ($sicAdvisorRequested) {
                $reason = 'SIC advisor explicitly requested';
            } elseif ($lead->isPaymentAuthorized()) {
                $reason = 'Lead has authorized payment';
            } elseif ($isPaymentAuthorizedOrDeclined) {
                $reason = 'Lead payment is authorized or declined';
            } elseif ($lead->hasRemainedUnauthorizedFor12Hours()) {
                $reason = 'Lead has remained unauthorized for 12 hours';
            } elseif ($hasRetryFlag) {
                $reason = 'Lead has retry flag (lead_allocation_failed_at)';
            }

            LoggerService::info(self::class.' - Device lead will be assigned to hardcoded advisors', extra: [
                'teamId' => $defaultTeamId,
                'reason' => $reason,
                'sicAdvisorRequested' => $sicAdvisorRequested,
                'hasRetryFlag' => $hasRetryFlag,
            ]);

            return $defaultTeamId;
        }

        // Lead doesn't meet allocation criteria - stop allocation (expected, not an error)
        LoggerService::info(self::class.' - Device lead does not require SIC advisor allocation - stopping', extra: [
            'reason' => 'Unpaid lead without SIC advisor request or retry flag or remained unauthorized for 12 hours',
        ]);

        $this->stop('Device lead does not require SIC advisor allocation', self::OK);
    }
}
