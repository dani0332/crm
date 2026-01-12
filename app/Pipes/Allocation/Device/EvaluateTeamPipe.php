<?php

namespace App\Pipes\Allocation\Device;

use App\Models\PersonalQuote;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\DeviceAllocation;
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

      

        // SIC advisor requested or has retry flag, assign to hardcoded advisors
        if ($sicAdvisorRequested || $hasRetryFlag) {
            $reason = $sicAdvisorRequested
                ? 'SIC advisor explicitly requested'
                : 'Lead has retry flag (lead_allocation_failed_at)';

            LoggerService::info(self::class.' - Device lead will be assigned to hardcoded advisors', extra: [
                'teamId' => $defaultTeamId,
                'reason' => $reason,
                'sicAdvisorRequested' => $sicAdvisorRequested,
                'hasRetryFlag' => $hasRetryFlag,
            ]);

            return $defaultTeamId;
        }

        // Lead doesn't meet allocation criteria - stop allocation
        LoggerService::info(self::class.' - sic advisor requested is false and no retry flag - Stopping allocation', extra: [
            'reason' => 'Unpaid lead without SIC advisor request or retry flag',
        ]);

        $this->stop('sic advisor requested is false for device lead', self::OK);
    }
}