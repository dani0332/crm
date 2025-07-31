<?php

namespace App\Pipes\Allocation\Travel;

use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
            LoggerService::info(self::class.' - Skipping advisor eligibility fetch');

            return $next($request);
        }

        $advisor = $this->fetchAvailableAdvisor();

        if (! $advisor) {
            LoggerService::info(self::class.' - No advisor found');

            if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
                LoggerService::info(self::class.' - Second call after reset - throwing exception');
                $this->allocationRequest->markAsFailed();
                $this->throw('Advisor not found', self::OK);
            } else {
                LoggerService::info(self::class.' - First call - continuing to ResetNationalityConfigPipe');

                return $next($request);
            }
        }

        $this->allocationRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    protected function fetchAvailableAdvisor()
    {
        // Use the team ID that was already evaluated in EvaluateTeamPipe
        $teamId = $this->allocationRequest->getTeamId();

        $advisors = $this->fetchEligibleAdvisors($teamId);

        $rules = $this->allocationRequest->get('rules') ?? [];
        $availableAdvisorIds = $advisors->pluck('user_id')->toArray() ?? [];
        LoggerService::info(message: self::class." - quote id: {$this->lead->uuid} available advisor ids: ".json_encode($availableAdvisorIds));

        $finalEligibleAdvisorIds = $this->determineFinalAdvisorIdsBasedOnRules($availableAdvisorIds, $rules, $teamId);
        $advisorId = $this->getFinalAdvisorId($finalEligibleAdvisorIds);

        $advisor = User::find($advisorId);

        return $advisor;
    }

    protected function getAdvisorsByStatus($onlineStatus, $teamId)
    {

        if ($this->allocationRequest->get('isCHSAdvisor')) {
            LoggerService::info(self::class.' - getAdvisorsByStatus: CHS Advisors is required');

            return User::select('users.id as user_id')->chs()->get();
        }

        if ($this->allocationRequest->get('isSICAdvisor')) {
            LoggerService::info(self::class.' - getAdvisorByStatus: SIC Advisor is required');

            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        return $this->getAdvisorBaseQuery($onlineStatus, $teamId, [RolesEnum::TravelAdvisor])
            ->when(! $teamId, function ($q) {
                $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
                if ($sicUnassistedTeamId) {
                    $q->whereNotIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $sicUnassistedTeamId));
                }
            })
            ->when($this->allocationRequest->isSIC(), function ($q) {
                $q->where('la.is_hardstop', true); // fetch users only with hardstop as true as they are eligible for allocation
            })
            ->logRawSql()
            ->get();
    }

    public function fetchEligibleAdvisors($teamId = null)
    {

        $statusOrder = $this->getOnlineStatusesInOrder();

        if ($this->lead->isPaymentAuthorizedOrPaymentLinkRequested()) {
            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        foreach ($statusOrder as $status) {
            LoggerService::info(message: self::class." - trying to get advisors with current status as {$status}");
            $eligibleUsers = $this->getAdvisorsByStatus($status, $teamId);

            if (count($eligibleUsers) > 0) {
                return $eligibleUsers;
            }
        }

        return [];

    }

    protected function determineFinalAdvisorIdsBasedOnRules($availableUserIds, $rules, $teamId = null): mixed
    {
        if (! $teamId) {
            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        if (count($rules) > 0) {
            // If there are rules, retrieve user IDs from the rule records.
            $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);

            LoggerService::info('Rule user IDs are: '.json_encode($ruleUserIds));
            // Find the intersection of available user IDs and rule user IDs.
            $finalEligibleUserIds = array_intersect($availableUserIds, $ruleUserIds);

            LoggerService::info('Rule found, and users against the rule are: '.json_encode($finalEligibleUserIds));
        } else {
            // If no rules are found, get user IDs from rule lead sources.
            $ruleUsers = (empty($teamId) || $teamId == 0) ? $this->allocationRequest->get('ruleUsers') : [];

            LoggerService::info('No rule found, so filtering rule users: '.json_encode($ruleUsers).' and teamId is : '.$teamId);

            // Find the difference between available user IDs and rule users.
            $finalEligibleUserIds = array_diff(
                $availableUserIds,
                is_array($ruleUsers) ? $ruleUsers : []
            );

            LoggerService::info('Final login and available users after rule exclusion are: '.json_encode($finalEligibleUserIds));
        }

        return $finalEligibleUserIds;
    }

    protected function getUserIdsFromRuleRecords($matchedRuleRecords): array
    {
        // Get the lead source users from the first matched rule record.
        $leadSourceUsers = $matchedRuleRecords->first()->leadSourceUsers;

        // Check if the lead source users contain a comma (,) indicating multiple users.
        if (str_contains($leadSourceUsers, ',')) {
            // If there are multiple users, split the string by commas, convert each part to an integer, and store them in an array.
            $userIds = array_map('intval', explode(',', $leadSourceUsers));
        } else {
            // If there's only one user, cast it to an integer and store it in a single-element array.
            $userIds = [(int) $leadSourceUsers];
        }

        // Return the array of user IDs.
        return $userIds;
    }
    protected function getFinalAdvisorId($finalEligibleUserIds)
    {
        // Return the first user ID from the final eligible user IDs if any, otherwise return 0.
        return count($finalEligibleUserIds) > 0 ? reset($finalEligibleUserIds) : 0;
    }

}
