<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\LeadSourceEnum;
use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Enums\TeamNameEnum;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\CommercialKeyword;
use App\Models\LeadSource;
use App\Models\Rule;
use App\Models\Team;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\User;
use App\Models\UserTeams;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;
use Illuminate\Support\Facades\DB;

class FetchTierUsersPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $lead = $this->allocationRequest->getLead();
        $tier = $this->allocationRequest->getTier();
        $teamId = $this->allocationRequest->getTeamId();

        // Find available users based on tier and lead source
        $tierUserIds = $this->getEligibleUserForAllocation(
            $tier,
            $this->allocationRequest->getReAssigFromAdvisorId(),
            $lead->source,
            $teamId,
            $lead
        );

        $this->allocationRequest->set('tierUserIds', $tierUserIds);

        $this->allocationRequest->set('ruleUsers', $this->getRuleUsers());

        return $next($request);
    }

    private function getEligibleUserForAllocation(Tier $tier, $advisorId, $leadSource, $teamId, CarQuote $lead)
    {
        // Get initial tier users
        $tierUserIds = $this->getTierUserIds($tier, $advisorId);
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

        // Apply rule-based exclusions if no rules exist for the lead
        return $this->applyRuleExclusions($tierUserIds, $lead);
    }

    private function getTierUserIds(Tier $tier, mixed $advisorId)
    {
        $tierUserIds = TierUser::where('tier_id', $tier->id)
            ->when($advisorId, function ($query) use ($advisorId) {
                $query->where('user_id', '!=', $advisorId);
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

    private function applyRuleExclusions($tierUserIds, $lead)
    {
        $rules = $this->getRulesForLeadSource($lead);

        $this->allocationRequest->set('rules', $rules);

        if ($rules->isEmpty()) {

            if ($lead->registration_type == CarRegistrationType::PERSONAL) {
                $ruleUserIds = $this->getRuleUsers(excludeVehicleUseRule: true);
            } else {
                $ruleUserIds = $this->getRuleUsers(excludeVehicleUseRule: false);
            }

            LoggerService::info('No rules found, excluding rule users: ', json_encode($ruleUserIds));

            return array_diff($tierUserIds, $ruleUserIds);
        }

        return $tierUserIds;
    }

    private function getRuleUsers($excludeVehicleUseRule = true): mixed
    {
        // Join the RuleLeadSource table with the Rules table where the rule is active (is_active = 1).
        // Select distinct user IDs associated with these rules and convert the result to an array.
        return Rule::join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->where('rules.is_active', 1)
            ->when($excludeVehicleUseRule, function ($query) {
                $query->where('rule_type', '!=', RuleTypeEnum::VEHICLE_USE);
            })
            ->distinct()
            ->pluck('rule_users.user_id')
            ->toArray();
    }

    private function getRulesForLeadSource($lead)
    {
        $commercialKeywords = CommercialKeyword::select('id', 'name')->get();

        $commercialCarMake = CarMake::where('id', $lead->car_make_id)
            ->where('is_commercial', true)
            ->select('id')
            ->first();

        $commercialCarModel = CarModel::where('id', $lead->car_model_id)
            ->where('is_commercial', true)
            ->select('id')
            ->first();

        foreach ($commercialKeywords as $keyword) {
            if (
                str_contains(
                    strtolower(trim($lead->full_name)),
                    strtolower(trim($keyword->name))
                )
                ||
                ($commercialCarMake && $commercialCarModel)
            ) {
                if ($lead->registration_type == CarRegistrationType::COMPANY) {
                    info(self::class."- Lead is registered as a company, applying vehicle use rules for lead with Ref-ID: {$lead->uuid} | Time: ".now());

                    return $this->getRulesForVehicleUse($lead);
                } else {
                    info(self::class."-Lead is not registered as a company, applying commercial rules for lead with Ref-ID:  {$lead->uuid} | Time: ".now());

                    return $this->getCommercialRule($lead);
                }
            }
        }

        LoggerService::info('keyword not found and vehicle is not commercial as well, so checking for normal rules');

        return $this->getUsersByLeadSourceRules($lead);
    }

    private function getUsersByLeadSourceRules($lead)
    {
        if ($lead->registration_type == CarRegistrationType::COMPANY) {
            info(self::class."- Lead is registered as a company, applying vehicle use rules for lead with Ref-ID: {$lead->uuid} | Time: ".now());

            return $this->getRulesForVehicleUse($lead);
        } else {
            $records = LeadSource::leftJoin('rule_details', 'rule_details.lead_source_id', 'lead_sources.id')
                ->join('rules', 'rules.id', 'rule_details.rule_id')
                ->join('rule_users', 'rule_users.rule_id', 'rules.id')
                ->join('users', 'users.id', 'rule_users.user_id')
                ->where('lead_sources.name', $lead->source)
                ->where('rules.is_active', 1)
                ->where('lead_sources.is_applicable_for_rules', 1)
                ->groupBy('rule_details.lead_source_id')
                ->select(
                    'lead_sources.name AS leadSourceName',
                    'lead_sources.id AS leadSourceId',
                    DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers')
                );
            LoggerService::info(self::class.'- Lead is not registered as a company, applying  rules for lead');

            return $records->get();
        }
    }

    private function getCommercialRule()
    {
        // Join relevant tables to retrieve commercial rules for car make and model.
        // Filter by rule type, ensure rules are active, and group by rule ID.
        // Select a concatenated list of user IDs as "leadSourceUsers" for each rule.
        return Rule::join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->join('users', 'users.id', 'rule_users.user_id')
            ->where('rule_type', RuleTypeEnum::CAR_MAKE_MODEL)
            ->where('rules.is_active', 1)
            ->groupBy('rule_details.rule_id')
            ->select([
                'rules.name AS ruleName',
                DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers'),
            ])->get();
    }

    private function getRulesForVehicleUse($lead)
    {
        if ($lead->vehicle_use == CarVehicleUse::PRIVATE) {
            info(self::class." - Lead is not commercial, applying private use rules for lead with Ref-ID: {$lead->uuid} | Time: ".now());

            return $this->getCompanyUsageRules($lead, RuleEnum::PRIVATE_USE->value);
        } else {
            info(self::class." - Lead is commercial, applying commercial use rules for lead with Ref-ID: {$lead->uuid} | Time: ".now());

            return $this->getCompanyUsageRules($lead, RuleEnum::COMMERCIAL_USE->value);
        }
    }

    private function getCompanyUsageRules($lead, $ruleName = null)
    {
        // if ($lead->source == LeadSourceEnum::INSURANCE_MARKET_CAR_QUOTE) {
        info(self::class." - Applying rule: {$ruleName} for lead with Ref-ID: {$lead->uuid} and source: {$lead->source} | Time: ".now());

        return Rule::join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->join('users', 'users.id', 'rule_users.user_id')
            ->where('rules.name', $ruleName)
            ->where('rule_type', RuleTypeEnum::VEHICLE_USE)
            ->where('rules.is_active', 1)
            ->groupBy('rule_details.rule_id')
            ->select(
                DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers')
            )->get();
    }
}
