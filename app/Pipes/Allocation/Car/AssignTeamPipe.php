<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class AssignTeamPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $lead = $this->allocationRequest->getLead();
        $teamId = $this->allocationRequest->getTeamId();

        // For SIC + PUA flow, adjust the team ID
        if ($teamId && $lead->isSIC(QuoteTypes::CAR) && $lead->isPUA()) {
            $sicTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
            if ($sicTeamId == $teamId) {
                $teamId = getTeamId(TeamNameEnum::ORGANIC);
                $this->allocationRequest->set('team_id', $teamId);
            }
        }

        // For AIG Lead with SIC advisor request
        if ($lead->isAIG(QuoteTypes::CAR) && empty($teamId) && $lead->sic_advisor_requested) {
            $teamId = getTeamId(TeamNameEnum::ORGANIC);
            $this->allocationRequest->set('team_id', $teamId);
            LoggerService::info('AIG lead detected with SIC advisor requested. Assigning to Organic team.');
        }

        return $next($request);
    }
}
