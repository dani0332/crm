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
    use Carable;

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
            $rules = $this->getRulesForLeadSource($lead);
            if ($rules->isNotEmpty()) {
                LoggerService::info(self::class.'::getEligibleUserForAllocation - Rules found, skipping team filter', [
                    'teamId' => $teamId,
                ]);
                $this->allocationRequest->setTeamId(null);
            } else {
                $tierUserIds = $this->filterUsersByTeam($tierUserIds, $teamId);
                LoggerService::info(self::class.'::getEligibleUserForAllocation - No rules found, filtering by team', [
                    'teamId' => $teamId,
                ]);
            }
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
        $lead = $this->allocationRequest->getLead();

        // Skip team filter for PUA leads with revival sources (REVIVAL_REPLIED, REVIVAL_PAID) or RENEWAL_UPLOAD source if ORGANIC team is assigned
        // This handles normal allocation flow where EvaluateTeamPipe sets teamId = ORGANIC
        $organicTeamId = getTeamId(TeamNameEnum::ORGANIC);
        if (in_array($leadSource, [LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID, LeadSourceEnum::RENEWAL_UPLOAD]) && $lead->isPUA() && $teamId == $organicTeamId) {
            LoggerService::info(self::class.'::executeRevivalAndRenewalCheck - Skipping team filter for PUA lead', [
                'leadSource' => $leadSource,
                'isPUA' => true,
                'assignedTeamId' => $teamId,
                'organicTeamId' => $organicTeamId,
                'paymentStatusId' => $lead->payment_status_id,
                'tierUserCount' => count($tierUserIds),
                'reason' => 'PUA + REVIVAL/RENEWAL with ORGANIC team assigned should skip team filter',
            ]);

            return $tierUserIds;
        }

        $teamMap = [
            LeadSourceEnum::REVIVAL_REPLIED => TeamNameEnum::ORGANIC,
            LeadSourceEnum::RENEWAL_UPLOAD => $teamId == 0 ? TeamNameEnum::ORGANIC : null,
            LeadSourceEnum::REVIVAL_PAID => TeamNameEnum::ORGANIC,
        ];

        if (isset($teamMap[$leadSource])) {
            $mappedTeam = $teamMap[$leadSource];
            LoggerService::info(self::class.'::executeRevivalAndRenewalCheck - Applying team filter', [
                'leadSource' => $leadSource,
                'mappedTeam' => $mappedTeam,
                'tierUserCountBefore' => count($tierUserIds),
            ]);

            // Retrieve team IDs for the relevant team
            $teamIds = Team::where('code', $mappedTeam)->active()->pluck('id')->toArray();

            // Retrieve user IDs associated with the relevant team
            $userIds = UserTeams::whereIn('team_id', $teamIds)->pluck('user_id')->toArray();

            // Get only the common user IDs
            $tierUserIds = array_intersect($tierUserIds, $userIds);

            LoggerService::info(self::class.'::executeRevivalAndRenewalCheck - After array_intersect', [
                'mappedTeam' => $mappedTeam,
                'tierUserCountAfter' => count($tierUserIds),
                'tierUsers' => $tierUserIds,
            ]);
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
