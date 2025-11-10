<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\UserStatusEnum;
use App\Models\BuyLeadRequest;
use App\Models\CarQuote;
use App\Models\LeadAllocation;
use App\Models\Team;
use App\Models\Tier;
use App\Models\UserTeams;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;
use App\Enums\LeadSourceEnum;
use App\Services\BuyLeads\BuyLeadService;

class FetchEligibleAdvisorsPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
            return $next($request);
        }

        $tierUserIds = $request->get('tierUserIds');
        $lead = $request->getLead();
        $tier = $request->getTier();
        $teamId = $request->getTeamId();

        $eligibleAdvisors = $this->fetchEligibleUsersByStatus($lead, $tier, $tierUserIds, $teamId);

        $request->set('eligibleAdvisors', $eligibleAdvisors);

        return $next($request);
    }

    private function fetchEligibleUsersByStatus(CarQuote $lead, Tier $tier, $tierUserIds, $teamId)
    {
        $tierUserIds = is_array($tierUserIds) ? $tierUserIds : $tierUserIds->toArray();
        LoggerService::info(self::class . "::fetchEligibleUsersByStatus - Users against tierID {$tier->id} and tier name: {$tier->name} are: " . json_encode($tierUserIds));

        $advisors = [];
        if($lead->source == LeadSourceEnum::REVIVAL && $lead->nationality_id && in_array($lead->nationality_id, app(BuyLeadService::class)->getCarCatANationalitiesIds())) {
            $advisors = $this->fetchAdvisors('getCATANationalitiesAdvisorsByStatus', $tier, $tierUserIds, $teamId);
        }
        if ($lead->isBuyLeadApplicable($this->allocationRequest->isSIC()) && ($tier->isValue() || $tier->isVolume())) {
            $advisors = $this->fetchAdvisors('getBLAdvisorsByStatus', $tier, $tierUserIds, $teamId);
        }

        if (empty($advisors)) {
            $advisors = $this->fetchAdvisors('getAdvisorsByStatus', $tier, $tierUserIds, $teamId);
        }

        return $advisors ?? [];
    }

    private function fetchAdvisors(string $findAdvisorFn, Tier $tier, $tierUserIds, $teamId)
    {
        $statusOrder = $this->getOnlineStatusesInOrder();

        $excludedUserIds = $this->getExcludedUserIds($teamId);
        $this->allocationRequest->set('excludedUserIds', $excludedUserIds);

        foreach ($statusOrder as $status) {
            $eligibleUsers = $this->{$findAdvisorFn}($status, $tier, $tierUserIds);

            if ($eligibleUsers && count($eligibleUsers) > 0) {
                LoggerService::info(self::class . "::{$findAdvisorFn} - Eligble Users found with the availability status of: " . UserStatusEnum::getUserStatusText($status));

                return $eligibleUsers->toArray();
            }
            LoggerService::info(self::class . "::{$findAdvisorFn} - No Users were found with the availability status of: " . UserStatusEnum::getUserStatusText($status));
        }

        return [];
    }

    private function getExcludedUserIds($teamId = null)
    {
        // Define a list of excluded team names.
        $excludedTeams = [TeamNameEnum::AFFINITY];

        // If team is not available, it should not be assigned.
        if (empty($teamId) || $teamId == 0 || $teamId == getTeamId(TeamNameEnum::ORGANIC)) {
            $excludedTeams[] = TeamNameEnum::SIC_UNASSISTED;
        }

        // Retrieve the IDs of excluded teams.
        $excludedTeamIds = Team::whereIn('name', $excludedTeams)->select('id')->get();

        // Retrieve the user IDs associated with excluded teams.
        return UserTeams::whereIn('team_id', $excludedTeamIds)->select('user_id')->pluck('user_id')->toArray();
    }

    private function getBaseQuery($status, $userIds)
    {
        $excludedUserIds = $this->allocationRequest->get('excludedUserIds');

        return LeadAllocation::whereHas('leadAllocationUser', function ($query) use ($status) {
            $query->where('status', $status)->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', [RolesEnum::CLIENTSUPPORT, RolesEnum::CLIENTSUPPORTLEAD]);
            });
        })
            ->whereIn('user_id', $userIds)
            ->when(! empty($excludedUserIds), function ($query) use ($excludedUserIds) {
                $query->whereNotIn('user_id', $excludedUserIds);
            })
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->when(
                $this->allocationRequest->hasNationalityConfig(),
                fn($q) => $q->whereIn('user_id', $this->allocationRequest->getAdvisorIDs()),
                function ($q) {
                    if ($this->allocationRequest->hasExcludedAdvisorIds()) {
                        $q->whereNotIn('user_id', $this->allocationRequest->getExcludedAdvisorIds());
                    }
                },
            )
            ->activeUser()
            ->when($this->allocationRequest->getReAssigFromAdvisorId(), fn($q) => $q->where('user_id', '!=', $this->allocationRequest->getReAssigFromAdvisorId()));
    }

    private function getBLAdvisorsByStatus($status, Tier $tier, $tierUserIds)
    {    
        LoggerService::info(self::class . "::getBLAdvisorsByStatus - trying to get advisors for tier : {$tier->name} with current status as {$status}");
        $buyLeadRequestedUserIds = BuyLeadRequest::getRequestedUserIds(QuoteTypes::CAR, $this->allocationRequest->isSIC(), $tier->isValue());
        LoggerService::info(self::class . '::getBLAdvisorsByStatus - buy lead requested user ids are: ' . json_encode($buyLeadRequestedUserIds));

        $userIds = array_values(array_intersect(
            $buyLeadRequestedUserIds,
            $tierUserIds
        ));

        $advisors = $this->getBaseQuery($status, $userIds)
            ->where('buy_lead_status', true)
            ->where(function ($query) {
                $query->whereRaw('buy_lead_allocation_count < buy_lead_max_capacity')->orWhere('buy_lead_max_capacity', '=', -1);
            })
            ->orderBy('buy_lead_last_allocated')
            ->logRawSql()
            ->get();

        if ($advisors->count() > 0) {
            $this->allocationRequest->set('hasBuyLeadAdvisors', true);

            LoggerService::info(self::class . '::getBLAdvisorsByStatus - Buy Lead Advisors ' . json_encode($advisors->pluck('user_id')->toArray()) . ' found');
        }

        return $advisors;
    }

    public function getAdvisorsByStatus($status, Tier $tier, $tierUserIds)
    {
        LoggerService::info(self::class . "::getAdvisorsByStatus - trying to get advisors for tier : {$tier->name} with current status as {$status}");

        return $this->getBaseQuery($status, $tierUserIds)
            ->where('normal_allocation_enabled', true)
            ->where(function ($query) {
                // Apply allocation count and max capacity conditions.
                $query->whereRaw('allocation_count < max_capacity')->orWhere('max_capacity', -1);
            })
            ->orderBy('last_allocated')
            ->logRawSql()
            ->get();
    }

    private function getCATANationalitiesAdvisorsByStatus($status, Tier $tier, $tierUserIds){
        $advisors = [];
            LoggerService::info(self::class . "::getCATANationalitiesAdvisorsByStatus - CAT A Nationality allocation for tier: {$tier->name} and status: {$status}");
            $buyLeadRequestedUserIds = BuyLeadRequest::getRevivalSourceUserIds($this->allocationRequest->isSIC(), $tier->isValue());
            LoggerService::info(self::class."::getCATANationalitiesAdvisorsByStatus - buy lead requested user ids are: ".json_encode($buyLeadRequestedUserIds));
            $userIds = array_values(array_intersect(
                $buyLeadRequestedUserIds,
                $tierUserIds
            ));
            $advisors = $this->getBaseQuery($status, $userIds)
                ->where('buy_lead_status', true)
                ->where(function ($query) {
                    $query->whereRaw('buy_lead_allocation_count < buy_lead_max_capacity')->orWhere('buy_lead_max_capacity', '=', -1);
                })
                ->orderBy('buy_lead_last_allocated')
                ->logRawSql()
                ->get();

            if ($advisors->count() > 0) {
                $this->allocationRequest->set('hasBuyLeadAdvisors', true);

                LoggerService::info(self::class . '::getCATANationalitiesAdvisorsByStatus - CAT A Buy Lead Advisors ' . json_encode($advisors->pluck('user_id')->toArray()) . ' found');
            }

        return $advisors ?? [];

    }
}
