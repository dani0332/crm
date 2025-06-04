<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\LeadSourceEnum;
use App\Enums\TeamNameEnum;
use App\Models\Team;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\UserTeams;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchTierUsersPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        // Find available users based on tier and lead source
        $tierUserIds = $this->getEligibleUserForAllocation();

        $this->allocationRequest->set('tierUserIds', $tierUserIds);

        return $next($request);
    }

    private function getEligibleUserForAllocation()
    {
        $lead = $this->allocationRequest->getLead();
        $tier = $this->allocationRequest->getTier();
        $teamId = $this->allocationRequest->getTeamId();
        $leadSource = $lead->source;

        // Get initial tier users
        $tierUserIds = $this->getTierUserIds($tier);
        $tierUserIds = $this->executeRevivalAndRenewalCheck($leadSource, $tierUserIds, $teamId);

        // Check if the lead qualifies for Organic team assignment (All Plan B Insurers, SIC, and no requested advisor)
        if ($lead->isEligibleForOrganicAssignmentForPlanB($this->allocationRequest->getQuoteType())) {
            LoggerService::info(self::class.'::fetchEligibleUsersByStatus - Lead qualifies for Organic team assignment with SIC flow enabled and no requested advisor');
            $teamId = getTeamId(TeamNameEnum::ORGANIC);
        }

        // Apply team filter if a team ID is provided
        if ($teamId) {
            $tierUserIds = $this->filterUsersByTeam($tierUserIds, $teamId);
        }

        return $tierUserIds;
    }

    private function getTierUserIds(Tier $tier)
    {
        $prevAdvisorId = $this->allocationRequest->getReAssigFromAdvisorId();

        $tierUserIds = TierUser::where('tier_id', $tier->id)
            ->when($prevAdvisorId, function ($query) use ($prevAdvisorId) {
                $query->where('user_id', '!=', $prevAdvisorId);
            })
            ->pluck('user_id')
            ->toArray();

        LoggerService::info("Users against Tier ID {$tier->id}: ".json_encode($tierUserIds));

        return $tierUserIds;
    }

    private function executeRevivalAndRenewalCheck($leadSource, $tierUserIds, $teamId): mixed
    {
        $teamMap = [
            LeadSourceEnum::REVIVAL_REPLIED => TeamNameEnum::ORGANIC,
            LeadSourceEnum::RENEWAL_UPLOAD => $teamId == 0 ? TeamNameEnum::ORGANIC : null,
            LeadSourceEnum::REVIVAL_PAID => TeamNameEnum::SIC_UNASSISTED,
        ];

        if (isset($teamMap[$leadSource])) {
            // Retrieve team IDs for the relevant team
            $teamIds = Team::where('name', $teamMap[$leadSource])->pluck('id')->toArray();

            // Retrieve user IDs associated with the relevant team
            $userIds = UserTeams::whereIn('team_id', $teamIds)->pluck('user_id')->toArray();

            // Get only the common user IDs
            $tierUserIds = array_intersect($tierUserIds, $userIds);
        }

        return $tierUserIds;
    }

    private function filterUsersByTeam($tierUserIds, $teamId)
    {
        $teamUserIds = UserTeams::where('team_id', $teamId)
            ->pluck('user_id')
            ->toArray();

        LoggerService::info("Team ID {$teamId} available users: ".json_encode($teamUserIds));

        return array_intersect($tierUserIds, $teamUserIds);
    }
}
