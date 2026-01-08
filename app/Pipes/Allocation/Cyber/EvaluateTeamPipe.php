<?php

namespace App\Pipes\Allocation\Cyber;

use App\Models\PersonalQuote;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class EvaluateTeamPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting team evaluation for Cyber lead');

        $this->setRequest($request);

        $lead = $this->allocationRequest->getLead();

        $teamId = $this->evaluateTeamId($lead);

        $this->allocationRequest->setTeamId($teamId);

        LoggerService::info(self::class.' - Team evaluation completed for Cyber lead', extra: [
            'teamId' => $teamId,
            'willUseTeam' => $teamId ? true : false,
        ]);

        return $next($request);
    }

    private function evaluateTeamId(PersonalQuote $lead)
    {
        $defaultTeamId = false;

        LoggerService::info(self::class.' - Cyber lead will be assigned to hardcoded advisors', extra: [
            'teamId' => $defaultTeamId,
        ]);

        return $defaultTeamId;
    }
}
