<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\CarPlanType;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RuleTypeEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TiersEnum;
use App\Enums\TiersIdEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\OCB\SendCarOCBIntroEmailJob;
use App\Models\BuyLeadRequest;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\CarQuotePlanDetail;
use App\Models\CarQuoteRequestDetail;
use App\Models\CommercialKeyword;
use App\Models\InsuranceProvider;
use App\Models\LeadAllocation;
use App\Models\LeadSource;
use App\Models\QuoteBatches;
use App\Models\Rule;
use App\Models\Team;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\User;
use App\Models\UserTeams;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CarAllocationService extends AllocationService
{
    public bool $isBuyLeadAdvisor = false;
    protected ?BuyLeadRequest $buyLeadRequest = null;

    protected function resetProps(): void
    {
        $this->isBuyLeadAdvisor = false;
        $this->buyLeadRequest = null;
    }

    public function endBuyLeadProcessing(): void
    {
        if ($this->buyLeadRequest) {
            $this->buyLeadRequest->completeProcessing();
        }
        $this->resetProps();
    }

    private function verifyPreChecks(CarQuote $lead, bool $overrideAssignment): bool
    {
        info(self::class." - Processing Car ILA for uuid: {$lead->uuid}", [
            'uuid' => $lead->uuid,
            'payment_status_id' => $lead->payment_status_id,
            'source' => $lead->source,
            'is_renewal_tier_email_sent' => $lead->is_renewal_tier_email_sent,
            'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
            'sic_flow_enabled' => $lead->sic_flow_enabled,
            'sic_advisor_requested' => $lead->sic_advisor_requested,
            'quote_status_id' => $lead->quote_status_id,
        ]);

        $continueAssignment = false;

        if (! $overrideAssignment && ! empty($lead->advisor_id)) {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} is already assigned to advisor with ID: {$lead->advisor_id}, skipping assignment");
        } elseif ($lead->isFakeOrDuplicate()) {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} is fake or duplicate having quote_status_id {$lead->quote_status_id}, skipping assignment");
        } elseif ($lead->hasExemptedSource()) {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} has exempted source {$lead->source}, skipping assignment");
        } elseif ($lead->isSICFlowEnabled() && $lead->isRequestedAdvisorOrPaymentAuthorized()) {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} has SIC flow enabled but either requested for an advisor or payment authorized, continuing assignment");
            $continueAssignment = true;
        } elseif ($lead->isRenewalTierEmailSent()) {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} has renewal tier email sent, skipping assignment");
        } elseif ($lead->isSICFlowDisabled() && ! $lead->isRenewalUpload()) {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} has SIC flow disabled and not Renewal Upload, skipping assignment");
            $continueAssignment = true;
        } elseif ($lead->isRevivalRepliedOrPaid()) {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} is a Revival lead, continuing assignment");
            $continueAssignment = true;
        } elseif ($lead->isRenewalUpload()) {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} is Renewal Upload, skipping assignment");
        } else {
            info(self::class."::verifyPreChecks - Lead with UUID: {$lead->uuid} does not meet any criteria, skipping assignment");
        }

        return $continueAssignment;
    }

    public function fetchLead($quoteId, $overrideAdvisorId)
    {
        // Check if Dubai Now exclusion should be applied
        $shouldIncludeDubaiNow = $this->getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;

        // List of exempted lead sources
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        // Add Dubai Now to exempted lead sources if $shouldIncludeDubaiNow is true
        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        $lead = CarQuote::where('uuid', $quoteId)->first();

        if (! $lead || ! $this->verifyPreChecks($lead, $overrideAdvisorId)) {
            return null;
        }

        return $lead;
    }

    public function getTier($tierId)
    {
        return Tier::where('id', $tierId)->first();
    }

    public function getTierBasedOnValue($carLead, $tiersQuery): void
    {
        if ($carLead->car_model_detail_id == null) {
            $tiersQuery->where('name', TiersEnum::TIER_L)->first();
        } else {
            $valuations = $this->getValuation($carLead->car_model_detail_id, $carLead->year_of_manufacture);

            $axaProvider = InsuranceProvider::where('code', InsuranceProvidersEnum::AXA)->first();

            $axaValuation = array_filter($valuations, function ($provider) use ($axaProvider) {
                return $provider->providerId == $axaProvider->id;
            });

            $carValue = 0;

            if (! empty($axaValuation)) {
                $firstAxaValuation = reset($axaValuation); // Get the first element of the array
                $carValue = $firstAxaValuation->carValue;
            }

            info('car value as per valuation engine for GIG is '.$carValue.' for lead : '.$carLead->uuid);
            $tiersQuery->where('min_price', '<=', $carValue)->where('max_price', '>=', $carValue);
        }
    }

    public function getExcludedUserIds($teamId = null)
    {
        // Define a list of excluded team names.
        $excludedTeams = [TeamNameEnum::AFFINITY];

        // If team is not available, it should not be assigned.
        if (empty($teamId) || $teamId == 0) {
            $excludedTeams[] = TeamNameEnum::SIC_UNASSISTED;
        }

        // Retrieve the IDs of excluded teams.
        $excludedTeamIds = Team::whereIn('name', $excludedTeams)->select('id')->get();

        // Retrieve the user IDs associated with excluded teams.
        $excludedUserIds = UserTeams::whereIn('team_id', $excludedTeamIds)->select('user_id')->get();

        return $excludedUserIds;
    }

    public function getTierUserIds($tierId, mixed $advisorId)
    {
        // Query to retrieve tier users based on the tier ID.
        $tierUserQuery = TierUser::where('tier_id', $tierId);

        // Exclude a specific advisor if an advisor ID is provided.
        if ($advisorId) {
            $tierUserQuery->where('user_id', '!=', $advisorId);
        }

        // Get the user IDs of eligible users from the tier.
        return $tierUserQuery->pluck('user_id');
    }

    public function getPlanAndYear($carLead): array
    {
        // Retrieve car quote plans with specific conditions.
        $plans = CarQuotePlanDetail::where('quote_uuid', $carLead->uuid)
            ->where('is_rating_available', true)
            ->where('repair_type', CarPlanType::COMP)->get();

        // Calculate the year of manufacture that is 15 years ago from the current date.
        $yearOfManufacture = now()->subYear(15)->year;

        info('yearOfManufacture is: '.$yearOfManufacture.' and number of plans found are: '.count($plans));

        return [$plans, $yearOfManufacture];
    }

    public function shouldEnforceSICCheck($lead, $tier): bool
    {
        [$plans, $yearOfManufacture] = $this->getPlanAndYear($lead);
        if ($tier->id == TiersIdEnum::TIER_5 && count($plans) > 0) {
            return true;
        }

        return false;
    }

    public function processSICFlow($lead, $tier): void
    {
        $lead->sic_flow_enabled = 1;
        $lead->tier_id = $tier->id;
        $lead->save();
        info('SIC flow is enabled for lead : '.$lead->uuid.' , the updated field : '.$lead->sic_flow_enabled);
        SendCarOCBIntroEmailJob::dispatch($lead->uuid, null, true);
        info('SIC flow is email is dispatched for lead : '.$lead->uuid);
    }

    /**
     * @return array|mixed
     */
    public function executeRevivalAndRenewalCheck($leadSource, $tierUserIds, $teamId): mixed
    {
        if ($leadSource == LeadSourceEnum::REVIVAL_REPLIED || ($leadSource == LeadSourceEnum::RENEWAL_UPLOAD && $teamId == 0)) {
            // if lead source is revival replied or renewal upload then we should only assign to organic advisors

            // Retrieve the ID of Organic team.
            $organicId = Team::whereIn('name', [TeamNameEnum::ORGANIC])->pluck('id')->toArray();

            // Retrieve the user IDs associated with organic team.
            $organicUserIds = UserTeams::whereIn('team_id', $organicId)->pluck('user_id')->toArray();

            // Getting common to get only organic advisors
            $tierUserIds = array_intersect($tierUserIds->toArray(), $organicUserIds);
        }

        return $tierUserIds;
    }

    protected function getDeferredLeads(): mixed
    {
        return CarQuote::whereNull('advisor_id')->where('deferred', 1)->whereBetween('deferred_at', [now()->subDay(2)->toDateTimeString(), now()]);
    }

    public function findTier($carLead): ?Tier
    {
        [$plans, $yearOfManufacture] = $this->getPlanAndYear($carLead);

        // Query to get all active tiers.
        $tiersQuery = Tier::where('is_active', 1);

        // Check if the car's year of manufacture is newer than 15 years.
        if ($carLead->year_of_manufacture < $yearOfManufacture) {
            // Check if more than one plan is found against the car lead.
            if (count($plans) > 0) {
                info('More than one plan found against car lead: '.$carLead->uuid);
                // Determine the tier based on a value and return the first matching tier.
                $this->getTierBasedOnValue($carLead, $tiersQuery);

                return $tiersQuery->first();
            } else {
                // Check car value and age to determine the tier.
                if ($carLead->car_value >= 300000) {
                    return $tiersQuery->Where('name', TiersEnum::TIER_H)->first();
                }

                $userDob = Carbon::createFromFormat('Y-m-d H:i:s', $carLead->dob);
                $ageInYears = $userDob->age;

                if ($carLead->car_value < 300000 || $ageInYears >= 21) {
                    return $tiersQuery->Where('name', $carLead->is_ecommerce ? TiersEnum::TIER6_ECOM : TiersEnum::TIER6_NONECOM)->first();
                }
            }
        } else {
            // Determine the tier based on a value and return the first matching tier.
            $this->getTierBasedOnValue($carLead, $tiersQuery);

            return $tiersQuery->first();
        }

        // Return null if no matching tier is found.
        return null;
    }

    public function findRenewalLeadTier($carLead): ?Tier
    {
        $isSICFlowEnabled = $carLead->sic_flow_enabled;
        $tiersQuery = Tier::where('is_active', 1)
            ->where('min_price', '<=', $carLead->car_value_tier)
            ->where('max_price', '>=', $carLead->car_value_tier)
            ->where('can_handle_tpl', 0)
            ->where('name', '!=', TiersEnum::TIER_R)
            ->where(function ($query) use ($isSICFlowEnabled) {
                if ($isSICFlowEnabled) {
                    $query->where('name', '!=', TiersEnum::TIER_L);
                }
            });

        $tier = $tiersQuery->first();

        if ($tier) {
            return $tier;
        }

        // Return null if no matching tier is found.
        return null;
    }

    public function getEligibleUserForAllocation(Tier $tier, $advisorId, $isReassignmentJob, $leadSource, $teamId, CarQuote $lead)
    {
        // reset these here because of dependency injection so new values each time
        $this->resetProps();

        // Get initial tier users
        $tierUserIds = $this->fetchTierUserIds($tier->id, $advisorId);
        $tierUserIds = $this->applyRevivalAndRenewalCheck($leadSource, $tierUserIds, $teamId);

        // Apply team filter if a team ID is provided
        if ($teamId) {
            $tierUserIds = $this->filterUsersByTeam($tierUserIds, $teamId);
        }

        // Apply rule-based exclusions if no rules exist for the lead
        $tierUserIds = $this->applyRuleExclusions($tierUserIds, $lead);

        // Get eligible users by status in the defined order
        $eligibleUsers = $this->fetchEligibleUsersByStatus($lead, $tier, $tierUserIds, $advisorId, $teamId, $isReassignmentJob);

        return $eligibleUsers ?: [];
    }

    // Helper methods

    private function fetchTierUserIds($tierId, $advisorId)
    {
        $tierUserIds = $this->getTierUserIds($tierId, $advisorId);
        info("Users against Tier ID {$tierId}: ".json_encode($tierUserIds->toArray()));

        return $tierUserIds;
    }

    private function applyRevivalAndRenewalCheck($leadSource, $tierUserIds, $teamId)
    {
        return $this->executeRevivalAndRenewalCheck($leadSource, $tierUserIds, $teamId);
    }

    private function filterUsersByTeam($tierUserIds, $teamId)
    {
        $teamUserIds = UserTeams::where('team_id', $teamId)
            ->pluck('user_id')
            ->toArray();

        info("Team ID {$teamId} available users: ".json_encode($teamUserIds));

        return array_intersect(is_array($tierUserIds) ? $tierUserIds : $tierUserIds->toArray(), $teamUserIds);
    }

    private function applyRuleExclusions($tierUserIds, $lead)
    {
        $rules = $this->getRules($lead);

        if ($rules->isEmpty()) {
            $ruleUserIds = $this->getRuleUsers();
            info("No rules found for lead ({$lead->uuid}), excluding rule users: ".json_encode($ruleUserIds));

            return array_diff(is_array($tierUserIds) ? $tierUserIds : $tierUserIds->toArray(), $ruleUserIds);
        }

        return $tierUserIds;
    }

    private function fetchAdvisors(string $findAdvisorFn, $tier, $tierUserIds, $advisorId, $teamId, $isReassignmentJob, $lead)
    {
        $statusOrder = $this->determineStatusOrder($isReassignmentJob);

        foreach ($statusOrder as $status) {
            $eligibleUsers = $this->{$findAdvisorFn}($lead, $status, $tier, $tierUserIds, $advisorId, $teamId);

            if ($eligibleUsers && count($eligibleUsers) > 0) {
                info(self::class."::fetchAdvisors - Eligble Users found for lead {$lead->uuid} with the availability status of: ".UserStatusEnum::getUserStatusText($status));

                return $eligibleUsers->toArray();
            }
            info(self::class."::fetchAdvisors - No Users were found for lead {$lead->uuid} with the availability status of: ".UserStatusEnum::getUserStatusText($status));
        }

        return [];
    }

    private function fetchEligibleUsersByStatus(CarQuote $lead, Tier $tier, $tierUserIds, $advisorId, $teamId, $isReassignmentJob)
    {
        $tierUserIds = is_array($tierUserIds) ? $tierUserIds : $tierUserIds->toArray();
        info(self::class."::fetchEligibleUsersByStatus - Users against tierID {$tier->id} and tier name: {$tier->name} for lead {$lead->uuid} are: ".json_encode($tierUserIds));

        $advisors = [];

        if ($lead->isBuyLeadApplicable($lead->isSIC(QuoteTypes::CAR)) && ($tier->isValue() || $tier->isVolume())) {
            $advisors = $this->fetchAdvisors('getBLAdvisorsByStatus', $tier, $tierUserIds, $advisorId, $teamId, $isReassignmentJob, $lead);
        }

        if (empty($advisors)) {
            $advisors = $this->fetchAdvisors('getAdvisorsByStatus', $tier, $tierUserIds, $advisorId, $teamId, $isReassignmentJob, $lead);
        }

        return $advisors;
    }

    private function determineStatusOrder($isReassignmentJob)
    {
        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        return $statusOrder;
    }

    public function updateTierBeforeEligibleUserIdentification($lead)
    {
        info('lead payment status is : '.$lead->payment_status_id.' and tier id is : '.$lead->tier_id.' and sic advisor requested is : '.$lead->sic_advisor_requested.' with UUID : '.$lead->uuid);
        if (($lead->payment_status_id == PaymentStatusEnum::AUTHORISED || $lead->sic_advisor_requested == 1) && $lead->tier_id == TiersIdEnum::TIER_R) {
            info('SIC lead payment is made and tier is Tier R lead with UUID: '.$lead->uuid);
            $tier = $this->findRenewalLeadTier($lead);
            if (! empty($tier) && $tier->id != $lead->tier_id) {
                info('Tier is found for the lead with UUID: '.$lead->uuid.' and tier name is: '.$tier->name);
                $this->updateLeadTier($lead, $tier);

                return $tier->id;
            } else {
                return $lead->tier_id;
            }
        }
    }

    private function getAdvisorBaseQuery($status, $userIds, $advisorId = null, $teamId = null)
    {
        $excludedUserIds = $this->getExcludedUserIds($teamId);

        $excludedUserIds = $excludedUserIds ? $excludedUserIds->pluck('user_id')->toArray() : [];

        // Create a query to fetch lead allocations with their associated users.
        $query = LeadAllocation::whereHas('leadAllocationUser', function ($query) use ($status) {
            // Filter by advisor status.
            $query->where('status', $status);
        })
            ->whereIn('user_id', $userIds)
            ->when(! empty($excludedUserIds), function ($query) use ($excludedUserIds) {
                $query->whereNotIn('user_id', $excludedUserIds);
            })
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->activeUser();

        // Exclude a specific advisor if an advisor ID is provided.
        if (! empty($advisorId)) {
            $query->where('user_id', '!=', $advisorId);
        }

        return $query;
    }

    public function getBLAdvisorsByStatus(CarQuote $lead, $status, Tier $tier, $tierUserIds, $advisorId = null, $teamId = null)
    {
        info(self::class."::getBLAdvisorsByStatus - trying to get advisors for tier : {$tier->name} with current status as {$status} for UUID: {$lead->uuid}");
        $buyLeadRequestedUserIds = BuyLeadRequest::getRequestedUserIds(QuoteTypes::CAR, $lead->isSIC(QuoteTypes::CAR), $tier->isValue());
        info(self::class.'::getBLAdvisorsByStatus - buy lead requested user ids are: '.json_encode($buyLeadRequestedUserIds));

        $userIds = array_values(array_intersect(
            $buyLeadRequestedUserIds,
            $tierUserIds
        ));

        $advisors = $this->getAdvisorBaseQuery($status, $userIds, $advisorId, $teamId)
            ->where('buy_lead_status', true)
            ->where(function ($query) {
                $query->whereRaw('buy_lead_allocation_count < buy_lead_max_capacity')->orWhere('buy_lead_max_capacity', '=', -1);
            })
            ->orderBy('buy_lead_last_allocated')
            ->get();

        $this->isBuyLeadAdvisor = $advisors->count() > 0;

        $advisors->count() > 0 && info(self::class.'::getBLAdvisorsByStatus - Buy Lead Advisors '.json_encode($advisors->pluck('user_id')->toArray())." found for uuid: {$lead->uuid}");

        return $advisors;
    }

    public function getAdvisorsByStatus($lead, $status, Tier $tier, $tierUserIds, $advisorId = null, $teamId = null)
    {
        info(self::class."::getAdvisorsByStatus - trying to get advisors for tier : {$tier->name} with current status as {$status} for UUID: {$lead->uuid}");

        return $this->getAdvisorBaseQuery($status, $tierUserIds, $advisorId, $teamId)
            ->where('normal_allocation_enabled', true)
            ->where(function ($query) {
                // Apply allocation count and max capacity conditions.
                $query->whereRaw('allocation_count < max_capacity')->orWhere('max_capacity', -1);
            })
            ->orderBy('last_allocated')
            ->get();
    }

    public function getRules($carLead)
    {
        return $this->getRulesForLeadSource($carLead);
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
                return $this->getCommercialRule();
            }
        }

        info('keyword not found and vehicle is not commercial as well, so checking for normal rules');

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

        return $records->get();
    }

    public function getCommercialRule()
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

    public function determineFinalUserId(CarQuote $lead, $eligibleUsers, $rules, $teamId, Tier $tier): mixed
    {
        // Extract user IDs from the eligible user data and convert them to an array.
        $availableUserIds = collect($eligibleUsers)->pluck('user_id')->toArray();
        info('Available User IDs are: '.json_encode($availableUserIds));

        if (count($rules) > 0) {
            // If there are rules, retrieve user IDs from the rule records.
            $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);

            info('Rule user IDs are: '.json_encode($ruleUserIds));

            // Find the intersection of available user IDs and rule user IDs.
            $finalEligibleUserIds = array_intersect($availableUserIds, $ruleUserIds);

            // Check if the lead source indicates a SAP lead.
            $isSAPLead = str_contains($lead->source, 'sap-') || str_contains($lead->source, 'partner.alfred.ae') || str_contains($lead->source, 'partner/car-insurance/gems');
            if ($isSAPLead) {
                // If the lead source is SAP, get eligible users for SAP leads.
                info('SAP lead found, so filtering eligible users for SAP lead');
                $finalEligibleUserIds = $this->getEligibleUserForSAPLead($ruleUserIds);
            }

            // if finalEligibleUserIds count is zero then it means all rule users are unavailable
            if (count($finalEligibleUserIds) == 0) {
                //  check if the found rule is commercial rule
                if ($rules->first()->ruleName == 'Commercial') {
                    info('All rule users are unavailable, so checking for commercial rule users regardless of availability.');
                    // if commercial then we need to assign lead to one of the $ruleUserIds based on max cap
                    // and other allocation criteria like round robin
                    $finalEligibleUserIds = $this->fetchUsersOnAllocationCriteria($ruleUserIds, $teamId);
                }
            }

            info('Rule found, and users against the rule are: '.json_encode($finalEligibleUserIds));
        } else {
            // If no rules are found, get user IDs from rule lead sources.
            $ruleUsers = (empty($teamId) || $teamId == 0) ? $this->getRuleUsers() : [];

            info('No rule found against this lead ('.$lead->uuid.'), so filtering rule users: '.json_encode($ruleUsers).' and teamId is : '.$teamId);

            // Find the difference between available user IDs and rule users.
            $finalEligibleUserIds = array_diff($availableUserIds, $ruleUsers);

            info('Final login and available users after rule exclusion are: '.json_encode($finalEligibleUserIds));
        }

        if ($this->isBuyLeadAdvisor) {
            foreach ($finalEligibleUserIds as $advisorId) {
                $this->buyLeadRequest = BuyLeadRequest::getRequest(QuoteTypes::CAR, $lead->isSIC(QuoteTypes::CAR), $advisorId, $tier->isValue());
                if ($this->buyLeadRequest) {
                    info("Buy Lead Request {$this->buyLeadRequest->id} found for advisor ID: {$advisorId} and tier ID: {$tier->id} for uuid : {$lead->uuid}");
                    $this->buyLeadRequest->startProcessing();

                    return $advisorId;
                }
            }

            return 0;
        }

        // Return the first user ID from the final eligible user IDs if any, otherwise return 0.
        return count($finalEligibleUserIds) > 0 ? reset($finalEligibleUserIds) : 0;
    }

    /**
     * @return array|int[]
     */
    private function getUserIdsFromRuleRecords($matchedRuleRecords): array
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

    private function getRuleUsers(): mixed
    {
        // Join the RuleLeadSource table with the Rules table where the rule is active (is_active = 1).
        // Select distinct user IDs associated with these rules and convert the result to an array.
        return Rule::join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->where('rules.is_active', 1)
            ->distinct()
            ->pluck('rule_users.user_id')
            ->toArray();
    }

    public function processLeadAssignment(CarQuote $lead, $userId, $tier, $assignmentType): void
    {
        info('About to assign car lead with UUID: '.$lead->uuid.' to user with ID: '.$userId);

        if ($lead->advisor_id === $userId) {
            info('Advisor is same as current advisor for lead : '.$lead->uuid.' so skipping assignment');

            $this->endBuyLeadProcessing();

            return;
        }

        if (! empty($lead->advisor_id) && $assignmentType !== AssignmentTypeEnum::SYSTEM_REASSIGNED) {
            $assignmentType = AssignmentTypeEnum::SYSTEM_REASSIGNED;
        }

        if ($assignmentType === AssignmentTypeEnum::SYSTEM_ASSIGNED && $this->isBuyLeadAdvisor) {
            $assignmentType = AssignmentTypeEnum::BOUGHT_LEAD;
        }

        if ($assignmentType === AssignmentTypeEnum::SYSTEM_REASSIGNED && $this->isBuyLeadAdvisor) {
            $assignmentType = AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD;
        }

        // Store the previous Assignment Type
        $previousAssignmentType = $lead->assignment_type;

        // Store the previous advisor ID.
        $previousUserId = $lead->advisor_id;

        // Assign the lead to the user and get the associated quote.
        $carQuote = $this->assignLeadToUserAndGetQuote($lead, $userId, $tier, $assignmentType);

        info('Advisor and tier assignment completed for lead with UUID: '.$carQuote->uuid.' to user with ID: '.$userId.' and tier name: '.$tier->name);

        // Update the car lead detail record and store the previous advisor assigned date.
        $previousAdvisorAssignedDate = $this->updateCarLeadDetailRecord($carQuote->id);

        info('Updating user record in lead allocation table with count increment for User ID: '.$userId);

        match ($assignmentType) {
            AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD => $this->addAllocationCounts($userId, QuoteTypes::CAR->id(), $this->isBuyLeadAdvisor),
            default => $this->adjustAllocationCounts($userId, $lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, QuoteTypes::CAR->id(), $this->isBuyLeadAdvisor),
        };

        info('Completed assignment of lead, and lead count update is done for quote with code: '.$carQuote->code);

        // Reset Buy Lead Advisor flag and Buy Lead Request object.
        $this->resetProps();

        info('Completed assignment of lead, and lead count update is done for quote with code: '.$carQuote->code);
    }

    private function assignLeadToUserAndGetQuote(CarQuote $lead, $userId, $tier, $assignmentType): mixed
    {
        // Assign the lead to the advisor and send an email
        // Check if the lead was previously assigned to an advisor and log the change.
        if (! empty($lead->advisor_id)) {
            info('Lead with UUID: '.$lead->uuid.' was previously assigned to User ID: '.$lead->advisor_id.' and is now being assigned to User ID: '.$userId);
        }

        // Update lead properties.
        $lead->tier_id = $tier->id;
        $lead->advisor_id = $userId;
        $lead->cost_per_lead = $tier->cost_per_lead;
        $lead->auto_assigned = true;
        $lead->sic_flow_enabled = 0;
        $lead->assignment_type = $assignmentType;

        // Get the latest quote batch and assign it to the lead.
        $quoteBatch = QuoteBatches::latest()->first();
        $lead->quote_batch_id = $quoteBatch->id;

        // Log information about the quote batch assignment.
        info('About to assign Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name.' to Quote with UUID: '.$lead->uuid);

        // Save the updated lead.
        $lead->save();

        if ($this->isBuyLeadAdvisor) {
            $this->buyLeadRequest->buyLead($lead, QuoteTypes::CAR);
            info('Lead Id '.$lead->uuid.' assigned to advisor id : '.$userId.' as bought lead');
        } else {
            info('Lead Id '.$lead->uuid.' assigned to advisor id : '.$userId);
        }

        $lead->endAllocation();

        return $lead;
    }

    public function updateCarLeadDetailRecord($leadId)
    {
        // Log information about the update operation.
        info('About to update car quote detail record for lead ID: '.$leadId);

        // Attempt to find an existing car quote detail record for the given lead.
        $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $leadId)->first();
        $oldAdvisorAssignedDate = $carQuoteDetail->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($leadId, CarQuoteRequestDetail::class, 'car_quote_request_id');

        // Return the old advisor assigned date, if applicable.
        return $oldAdvisorAssignedDate;
    }

    public function fetchLeadsForReAssignment($advisorId)
    {
        // Calculate the start date for lead retrieval
        $from = now()->subDay()->setTime(12, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        info('Leads will be picked up in reassignment from : '.$from.' until : '.now()->toDateTimeString());

        // Check if Dubai Now exclusion should be applied
        $shouldIncludeDubaiNow = $this->getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;

        // List of exempted lead sources
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        // Add Dubai Now to exempted lead sources if $shouldIncludeDubaiNow is true
        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        // Get the Tier R
        $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

        // Query to fetch leads
        $leads = CarQuote::whereBetween('created_at', [$from, now()])
            ->whereNotIn('source', $exemptedLeadSources)
            ->where('quote_status_id', QuoteStatusEnum::NewLead)
            ->where('is_renewal_tier_email_sent', 0);

        // Filter by advisor ID if provided , which mean reassignment is going to run for a single advisor
        if ($advisorId != 0) {
            $leads->where('advisor_id', $advisorId);
            info('Inside reassignment single run and selected advisor is: '.$advisorId);
        } else {
            // If advisor ID is not provided, get unavailable advisors and filter leads by them
            $advisors = $this->getUnavailableAdvisor();
            if (count($advisors) > 0) {
                $advisorIds = $advisors->pluck('user_id');
                info('Inside reassignment general run');
                $leads->whereIn('advisor_id', $advisorIds);
            }
        }

        // Filter leads by tier (if applicable)
        if (! empty($tierR)) {
            $leads->where('tier_id', '!=', $tierR->id);
        }

        return $leads->get();
    }

    public function shouldProceed(): bool
    {
        return $this->shouldProceedWithReAllocation('constants.CAR_LEAD_ALLOCATION_MASTER_SWITCH');
    }

    public function isLeadReassigned($lead)
    {
        $leadDetail = CarQuoteRequestDetail::where('car_quote_request_id', $lead->id)->first();

        return $leadDetail && $leadDetail->advisor_assigned_date > now()->subMinutes(2);
    }

    public function updateLeadTier($lead, $tier): void
    {
        CarQuote::where('id', $lead->id)->update([
            'tier_id' => $tier->id,
        ]);

        info('Tier with name : '.$tier->name.' is assigned to car lead with uuid : '.$lead->uuid);
    }

    public function getEligibleUserForSAPLead($ruleUserIds): array
    {
        // Create a query to fetch lead allocations with their associated users.
        return LeadAllocation::with('leadAllocationUser')
            ->activeUser()
            ->whereIn('user_id', $ruleUserIds) // it will be the rule user ids for SAP rule only
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->orderBy('last_allocated')
            ->pluck('user_id')->toArray();
    }

    public function fetchUsersOnAllocationCriteria($ruleUserIds, $teamId)
    {
        $excludedUserIds = $this->getExcludedUserIds($teamId);

        // Create a query to fetch lead allocations with their associated users.
        return LeadAllocation::where(function ($query) {
            // Apply allocation count and max capacity conditions.
            $query->whereRaw('allocation_count < max_capacity')
                ->orWhere('max_capacity', -1);
        })
            ->whereIn('user_id', $ruleUserIds)
            ->whereNotIn('user_id', $excludedUserIds)
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->activeUser()
            ->orderBy('last_allocated')
            ->pluck('user_id')
            ->toArray();
    }
}
