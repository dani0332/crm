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
        $isPaymentAuthorizedOrLinkRequestedOrDeclined = $this->lead->isPaymentAuthorizedOrLinkRequestedOrDeclined();
        $isLeadFromInstantAlfred = $this->lead->isLeadFromInstantAlfred();

        $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        $isAIGWithInstantAlfred = $isAIG && $isLeadFromInstantAlfred;
        $isSICOrAIGWithPayment = (($isSIC && ! $isAIG) || $isAIG) && $isPaymentAuthorizedOrLinkRequestedOrDeclined;
        $isNonSICNonAIGWithPayment = (! $isSIC && ! $isAIG) && $isPaymentAuthorizedOrLinkRequestedOrDeclined;

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
            'isPaymentAuthorizedOrLinkRequestedOrDeclined' => $isPaymentAuthorizedOrLinkRequestedOrDeclined,
            'isLeadFromInstantAlfred' => $isLeadFromInstantAlfred,
        ]);

        return $teamId;
    }

    private function shouldSkipNationalityValidation(): void
    {
        $isPaymentAuthorizedOrLinkRequestedOrDeclined = $this->lead->isPaymentAuthorizedOrLinkRequestedOrDeclined();

        if ($isPaymentAuthorizedOrLinkRequestedOrDeclined) {
            LoggerService::info('Skipping nationality validation for travel quote with payment authorized or link requested or declined or failed');
            $this->allocationRequest->set('skipNationalityValidation', true);
        }
    }
}
