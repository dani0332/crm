<?php

namespace App\Pipes\Allocation\Travel;

use App\Enums\TeamNameEnum;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class EvaluateTeamPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     *
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $teamId = $this->evaluateTeamId();

        $this->allocationRequest->setTeamId($teamId);

        $this->shouldSkipNationalityValidation();

        return $next($request);
    }

    private function evaluateTeamId()
    {
        $defaultTeamId = false;

        $isSIC = $this->allocationRequest->isSIC();
        $isAIG = $this->allocationRequest->isAIG();
        $isPaymentAuthorizedOrLinkRequested = $this->lead->isPaymentAuthorizedOrLinkRequested();
        $isLeadFromInstantAlfred = $this->lead->isLeadFromInstantAlfred();

        $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        $isAIGWithInstantAlfred = $isAIG && $isLeadFromInstantAlfred;
        $isSICOrAIGWithPayment = (($isSIC && ! $isAIG) || $isAIG) && $isPaymentAuthorizedOrLinkRequested;
        $isNonSICNonAIGWithPayment = (! $isSIC && ! $isAIG) && $isPaymentAuthorizedOrLinkRequested;

        $teamId = null;

        // Apply team assignment rules
        if ($isAIGWithInstantAlfred) {
            // Rule 1: AIG leads from Instant Alfred go to default team
            $teamId = $defaultTeamId;
            $reason = 'AIG and Lead from Instant Alfred';
        } elseif ($isSICOrAIGWithPayment) {
            // Rule 2: SIC or AIG leads with payment authorized or link requested
            $teamId = $sicUnassistedTeamId;
            $reason = $isAIG ? 'AIG with payment authorized or link requested' :
                              'SIC with payment authorized or link requested';
        } elseif ($isNonSICNonAIGWithPayment) {
            // Rule 3: Non-SIC, Non-AIG leads with payment authorized or link requested
            $teamId = $sicUnassistedTeamId;
            $reason = 'Non-SIC, Non-AIG lead with payment authorized or link requested';
        } else {
            // Rule 4: Default - all other leads have no specific team
            $teamId = $defaultTeamId;
            $reason = 'Default case - no specific team';
        }

        // Log the final team assignment using debug with extra parameter
        LoggerService::debug('Team assigned for Travel Allocation', extra: [
            'reason' => $reason,
            'teamId' => $teamId,
            'isSIC' => $isSIC,
            'isAIG' => $isAIG,
            'isPaymentAuthorizedOrLinkRequested' => $isPaymentAuthorizedOrLinkRequested,
            'isLeadFromInstantAlfred' => $isLeadFromInstantAlfred,
        ]);

        return $teamId;
    }

    private function shouldSkipNationalityValidation(): void
    {
        $isPaymentAuthorizedOrLinkRequested = $this->lead->isPaymentAuthorizedOrLinkRequested();

        if ($isPaymentAuthorizedOrLinkRequested) {
            LoggerService::info('Skipping nationality validation for travel quote with payment authorized or link requested');
            $this->allocationRequest->set('skipNationalityValidation', true);
        }
    }
}
