<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\BuyLeadRequest;
use App\Models\HealthQuote;
use App\Models\Team;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\HealthEmailService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    protected bool $isBuyLeadAdvisor = false;
    protected $buyLeadRequest = null;
    protected ?array $ruleUserIds = null;

    /**
     * Handle the incoming request.
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
            LoggerService::warning('No advisors found');

            if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
                LoggerService::info(self::class.' - Second call after reset - throwing exception');
                $this->allocationRequest->markAsFailed();

                // Check if we need to send an apply now email
                if ($this->lead->isApplicationPending() && ! $this->lead->isApplyNowEmailSent() && Carbon::parse($this->lead->quote_status_date)->lessThanOrEqualTo(now()->subMinutes(10))) {
                    LoggerService::info("Sending Apply Now Email as it's been 10 minutes since quote status was marked as application pending");
                    app(HealthEmailService::class)->initiateApplyNowEmail($this->lead);
                }

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

    private function getTeamId()
    {
        $parentTeamId = Team::where('name', TeamNameEnum::HEALTH)->active()->where('type', TeamTypeEnum::PRODUCT)->value('id');
        $teamId = Team::where('name', $this->lead->health_team_type ?? $this->lead->notional_team)->active()->where('type', TeamTypeEnum::TEAM)->where('parent_team_id', $parentTeamId)->value('id');
        LoggerService::info(sprintf('[FetchAvailableAdvisorPipe@getTeamId] Resolving team IDs for lead: uuid=%s, health_team_type=%s', $this->lead->uuid, $this->lead->health_team_type ?? 'N/A'), ['parentTeamId' => $parentTeamId ?? 'null', 'teamId' => $teamId ?? 'null']);

        return $teamId;
    }
    protected function fetchAvailableAdvisor()
    {
        $teamId = $this->getTeamId();
        $advisors = $this->fetchEligibleAdvisors(true, $teamId);
        $availableAdvisorIds = $advisors->pluck('user_id')->toArray() ?? [];
        $rules = $this->allocationRequest->get('rules') ?? [];
        $finalEligibleAdvisorIds = $this->determineFinalAdvisorIdsBasedOnRules($this->lead, $availableAdvisorIds, $rules, $teamId);

        $advisorId = $this->getFinalAdvisorId($finalEligibleAdvisorIds);

        $advisor = User::find($advisorId);

        return $advisor;
    }

    protected function fetchEligibleAdvisors(bool $onlineStatus = true, $teamId = null)
    {
        $advisors = collect([]);

        $rules = $this->allocationRequest->get('rules');
        if (! empty($rules) && $rules->isNotEmpty()) {
            $this->ruleUserIds = $this->allocationRequest->get('ruleUserIds', []);

            LoggerService::info(self::class.'::fetchEligibleAdvisors - rule user ids are: '.json_encode($this->ruleUserIds));
        }

        if ($this->lead->isBuyLeadApplicable($this->allocationRequest->isSIC()) && ($this->lead->isValueLead() || $this->lead->isVolumeLead())) {
            $advisors = $this->fetchAdvisorByType('getBLAdvisorsByStatus', $teamId);
        }

        if (count($advisors) < 1) {
            $advisors = $this->fetchAdvisorByType('getAdvisorsByStatus', $teamId);
        }

        return $advisors;
    }
    protected function fetchAdvisorByType(string $methodName, $teamId = null)
    {
        $statusOrder = $this->getOnlineStatusesInOrder();
        $eligibleUsers = [];

        foreach ($statusOrder as $status) {
            LoggerService::info(self::class."::fetchAdvisorByType - trying to get advisors for team: {$this->lead->health_team_type} with current status as {$status}");

            $eligibleUsers = $this->{$methodName}($status, $teamId);
            if (count($eligibleUsers) > 0) {
                LoggerService::info(self::class.'::fetchAdvisorByType - advisors found: '.json_encode($eligibleUsers->pluck('user_id')->toArray()));

                return $eligibleUsers;
            }
        }

        return $eligibleUsers;
    }

    protected function getBLAdvisorsByStatus($status, $teamId = null)
    {
        LoggerService::info(self::class."::getBLAdvisorByStatus - trying to get advisors for team: {$this->lead->health_team_type} with current status as {$status}");

        $buyLeadRequestedUserIds = BuyLeadRequest::getRequestedUserIds(
            $this->allocationRequest->getQuoteType(),
            $this->allocationRequest->isSIC(),
            $this->lead->isValueLead()
        );

        LoggerService::info(self::class.'::getBLAdvisorsByStatus - buy lead requested user ids are: '.json_encode($buyLeadRequestedUserIds));

        if (! is_null($this->ruleUserIds)) {
            $userIds = array_values(array_intersect(
                $buyLeadRequestedUserIds,
                $this->ruleUserIds
            ));

            LoggerService::info(self::class.'::getBLAdvisorsByStatus - user ids after intersection with rule users are: '.json_encode($userIds));
        } else {
            $userIds = $buyLeadRequestedUserIds;
        }

        $advisors = $this->getAdvisorBaseQuery($status, $teamId, [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor], true)
            ->when($this->lead->isValueLead(), function ($q) {
                $q->isValueUser($this->allocationRequest->getQuoteType());
            }, function ($q) {
                $q->isVolumeUser($this->allocationRequest->getQuoteType());
            })
            ->whereIn('users.id', $userIds)
            ->logRawSql()
            ->get();

        if (count($advisors) > 0) {
            $this->allocationRequest->set('hasBuyLeadAdvisors', true);
            LoggerService::info(self::class.'::getBLAdvisorsByStatus - Buy Lead Advisors '.json_encode($advisors->pluck('user_id')->toArray()).' found');
        }

        return $advisors;
    }

    protected function getAdvisorsByStatus($onlineStatus, $teamId = null)
    {
        LoggerService::info(self::class."::getAdvisorsByStatus - trying to get advisors for team: {$this->lead->health_team_type} with current status as {$onlineStatus}");

        $advisors = $this->getAdvisorBaseQuery($onlineStatus, $teamId, [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor])
            ->where('la.normal_allocation_enabled', true)
            ->when(! is_null($this->ruleUserIds), function ($q) {
                $q->whereIn('users.id', $this->ruleUserIds);
            })
            ->logRawSql()
            ->get();

        return $advisors;
    }

    protected function determineFinalAdvisorIdsBasedOnRules(HealthQuote $lead, $availableUserIds, $rules, $teamId): mixed
    {
        if (count($rules) > 0) {
            // If there are rules, retrieve user IDs from the rule records.
            $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);

            LoggerService::info('Rule user IDs are: '.json_encode($ruleUserIds));

            // Find the intersection of available user IDs and rule user IDs.
            $finalEligibleUserIds = array_intersect($availableUserIds, $ruleUserIds);

            LoggerService::info('Rule found, and users against the rule are: '.json_encode($finalEligibleUserIds));
        } else {
            // If no rules are found, get user IDs from rule lead sources.
            $ruleUserIds = $this->allocationRequest->get('ruleUserIds');
            $ruleUserIds = $this->finalizeExcludedAdvisorIds($ruleUserIds);

            LoggerService::info('No rule found, so filtering rule users: '.json_encode($ruleUserIds).' and teamId is : '.$teamId);

            // Ensure $ruleUsers is always an array to avoid array_diff() error.
            $finalEligibleUserIds = array_diff(
                $availableUserIds,
                is_array($ruleUserIds) ? $ruleUserIds : []
            );

            LoggerService::info('Final login and available users after rule exclusion are: '.json_encode($finalEligibleUserIds));
        }

        return $finalEligibleUserIds;
    }
    protected function getUserIdsFromRuleRecords($matchedRuleRecords): array
    {
        // Get the lead source users from the first matched rule record.
        $leadSourceUsers = $matchedRuleRecords->first()->leadSourceUsers ?? null;

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
        if ($this->allocationRequest->get('hasBuyLeadAdvisors')) {
            return $this->evaluateBuyLeadAdvisor($finalEligibleUserIds);
        }

        // Return the first user ID from the final eligible user IDs if any, otherwise return 0.
        return count($finalEligibleUserIds) > 0 ? reset($finalEligibleUserIds) : 0;
    }

    protected function evaluateBuyLeadAdvisor($finalEligibleUserIds)
    {
        foreach ($finalEligibleUserIds as $advisorId) {
            $buyLeadRequest = BuyLeadRequest::getRequest(
                $this->allocationRequest->getQuoteType(),
                $this->allocationRequest->isSIC(),
                $advisorId,
                $this->lead->isValueLead(),
            );

            if ($buyLeadRequest) {
                $this->allocationRequest->setBuyLeadRequest($buyLeadRequest);

                LoggerService::info("Buy Lead Request {$buyLeadRequest->id} found for advisor ID: {$advisorId}  ");
                $this->allocationRequest->getBuyLeadRequest()->startProcessing();

                return $advisorId;
            }
        }

        return null;
    }
}
