<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\CarPlanType;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RuleTypeEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TiersEnum;
use App\Enums\TiersIdEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\SendOCBIntroEmailJob;
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
use App\Models\RuleLeadSource;
use App\Models\Team;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\User;
use App\Models\UserTeams;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarAllocationService extends AllocationService
{
    public function fetchLead($quoteId, $overrideAdvisorId)
    {
        // Check if Dubai Now exclusion should be applied
        $shouldIncludeDubaiNow = $this->getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;

        // List of exempted lead sources
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        // Add Dubai Now to exempted lead sources if $shouldIncludeDubaiNow is true
        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        // Create a query to retrieve a car lead based on the provided quote ID and filters.
        $carQuoteQuery = CarQuote::where('uuid', $quoteId)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', $exemptedLeadSources)
            ->where('is_renewal_tier_email_sent', 0);

        if (!$overrideAdvisorId) {
            $carQuoteQuery->whereNull('advisor_id');
        }

        return $carQuoteQuery->first();
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

            if (!empty($axaValuation)) {
                $firstAxaValuation = reset($axaValuation); // Get the first element of the array
                $carValue = $firstAxaValuation->carValue;
            }

            info('car value as per valuation engine for GIG is ' . $carValue . ' for lead : ' . $carLead->uuid);
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

        info('yearOfManufacture is: ' . $yearOfManufacture . ' and number of plans found are: ' . count($plans));

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
        info('SIC flow is enabled for lead : ' . $lead->uuid . ' , the updated field : ' . $lead->sic_flow_enabled);
        SendOCBIntroEmailJob::dispatch($lead->uuid, null, true);
        info('SIC flow is email is dispatched for lead : ' . $lead->uuid);
    }

    /**
     * @return array|mixed
     */
    public function executeRevivalCheck($leadSource, $tierUserIds): mixed
    {
        if ($leadSource == LeadSourceEnum::REVIVAL_REPLIED) {
            // if lead source is revival replied then we should only assign to organic advisors

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
                info('More than one plan found against car lead: ' . $carLead->uuid);
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

    public function getEligibleUserForAllocation($tierId, $advisorId, $isReassignmentJob, $leadSource, $teamId)
    {
        $tierUserIds = $this->getTierUserIds($tierId, $advisorId);

        $tierUserIds = $this->executeRevivalCheck($leadSource, $tierUserIds);

        if ($teamId) {
            $teamUserIds = UserTeams::where('team_id', $teamId)->select('user_id')->get();
            if ($teamUserIds->count() > 0) {
                $teamUserIds = $teamUserIds->pluck('user_id')->toArray();
            } else {
                $teamUserIds = [];
            }
            $tierUserIds = array_intersect($tierUserIds->toArray(), $teamUserIds);
        }

        // Define the order in which user statuses should be considered.
        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (!$isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        // Iterate through user statuses in the specified order.
        foreach ($statusOrder as $status) {
            // Get eligible users with the specified status.
            $eligibleUsers = $this->getAdvisorsByStatus($status, $tierUserIds, $advisorId, $teamId);

            // If eligible users are found, log the results and return them.
            if ($eligibleUsers && count($eligibleUsers) > 0) {
                return $eligibleUsers->toArray();
            }
        }

        // If no eligible users are found, return an empty array.
        return [];
    }

    public function getAdvisorsByStatus($status, $tierUserIds, $advisorId = null, $teamId = null)
    {
        $excludedUserIds = $this->getExcludedUserIds($teamId);

        // Create a query to fetch lead allocations with their associated users.
        $query = LeadAllocation::with('leadAllocationUser')
            ->whereHas('leadAllocationUser', function ($query) use ($status) {
                // Filter by advisor status.
                $query->where('status', $status);
            })
            ->where(function ($query) {
                // Apply allocation count and max capacity conditions.
                $query->whereRaw('allocation_count < max_capacity')
                    ->orWhere('max_capacity', -1);
            })
            ->whereIn('user_id', $tierUserIds)
            ->whereNotIn('user_id', $excludedUserIds)
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->orderBy('last_allocated');

        // Exclude a specific advisor if an advisor ID is provided.
        if (!empty($advisorId)) {
            $query->where('user_id', '!=', $advisorId);
        }

        // Return the resulting collection of advisors.
        return $query->get();
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
                DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers')
            ])->get();
    }

    public function determineFinalUserId($lead, $eligibleUsers, $rules, $teamId): mixed
    {
        // Extract user IDs from the eligible user data and convert them to an array.
        $availableUserIds = collect($eligibleUsers)->pluck('user_id')->toArray();

        if (count($rules) > 0) {
            // If there are rules, retrieve user IDs from the rule records.
            $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);

            info('Rule user IDs are: ' . json_encode($ruleUserIds));

            // Find the intersection of available user IDs and rule user IDs.
            $finalEligibleUserIds = array_intersect($availableUserIds, $ruleUserIds);

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

            info('Rule found, and users against the rule are: ' . json_encode($finalEligibleUserIds));
        } else {
            // If no rules are found, get user IDs from rule lead sources.
            $ruleUsers = (empty($teamId) || $teamId == 0) ? $this->getRuleUsers() : [];

            info('No rule found against this lead (' . $lead->uuid . '), so filtering rule users: ' . json_encode($ruleUsers) . ' and teamId is : ' . $teamId);

            // Find the difference between available user IDs and rule users.
            $finalEligibleUserIds = array_diff($availableUserIds, $ruleUsers);

            info('Final login and available users after rule exclusion are: ' . json_encode($finalEligibleUserIds));
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

    public function processLeadAssignment($lead, $userId, $tier, $assignmentType): void
    {
        info('About to assign car lead with UUID: ' . $lead->uuid . ' to user with ID: ' . $userId);

        //Store the previous Assignment Type
        $previousAssignmentType = $lead->assignment_type;

        // Store the previous advisor ID.
        $previousUserId = $lead->advisor_id;

        // Assign the lead to the user and get the associated quote.
        $carQuote = $this->assignLeadToUserAndGetQuote($lead, $userId, $tier, $assignmentType);

        info('Advisor and tier assignment completed for lead with UUID: ' . $carQuote->uuid . ' to user with ID: ' . $userId . ' and tier name: ' . $tier->name);

        // Update the car lead detail record and store the previous advisor assigned date.
        $previousAdvisorAssignedDate = $this->updateCarLeadDetailRecord($carQuote->id);

        info('Updating user record in lead allocation table with count increment for User ID: ' . $userId);

        // Depending on the assignment type, either add or adjust allocation counts.
        $assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED ? $this->addAllocationCounts($userId, QuoteTypes::CAR->id()) : $this->adjustAllocationCounts($userId, $lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, QuoteTypes::CAR->id());

        info('Completed assignment of lead, and lead count update is done for quote with code: ' . $carQuote->code);
    }

    private function assignLeadToUserAndGetQuote($lead, $userId, $tier, $assignmentType): mixed
    {
        // Assign the lead to the advisor and send an email
        // Check if the lead was previously assigned to an advisor and log the change.
        if (!empty($lead->advisor_id)) {
            info('Lead with UUID: ' . $lead->uuid . ' was previously assigned to User ID: ' . $lead->advisor_id . ' and is now being assigned to User ID: ' . $userId);
        }

        // Update lead properties.
        $lead->tier_id = $tier->id;
        $lead->advisor_id = $userId;
        $lead->cost_per_lead = $tier->cost_per_lead;
        $lead->auto_assigned = true;
        $lead->assignment_type = $assignmentType;

        // Get the latest quote batch and assign it to the lead.
        $quoteBatch = QuoteBatches::latest()->first();
        $lead->quote_batch_id = $quoteBatch->id;

        // Log information about the quote batch assignment.
        info('About to assign Quote Batch with ID: ' . $quoteBatch->id . ' and Name: ' . $quoteBatch->name . ' to Quote with UUID: ' . $lead->uuid);

        // Save the updated lead.
        $lead->save();

        return $lead;
    }

    public function updateCarLeadDetailRecord($leadId)
    {
        // Log information about the update operation.
        info('About to update car quote detail record for lead ID: ' . $leadId);

        // Attempt to find an existing car quote detail record for the given lead.
        $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $leadId)->first();

        // Initialize a variable to store the old advisor assigned date.
        $oldAdvisorAssignedDate = '';

        if ($carQuoteDetail) {
            // If a car quote detail record exists, store its old advisor assigned date.
            $oldAdvisorAssignedDate = $carQuoteDetail->advisor_assigned_date;

            // Update the existing quote detail record.
            $this->updateExistingQuoteDetail($carQuoteDetail, $leadId);
        } else {
            // If no car quote detail record exists, create a new one.
            $this->createNewQuoteDetail($leadId, CarQuoteRequestDetail::class, 'car_quote_request_id');
        }

        // Return the old advisor assigned date, if applicable.
        return $oldAdvisorAssignedDate;
    }

    public function fetchLeadsForReAssignment($advisorId)
    {
        // Calculate the start date for lead retrieval
        $from = now()->subDay()->setTime(12, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        info('Leads will be picked up in reassignment from : ' . $from . ' until : ' . now()->toDateTimeString());

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
            info('Inside reassignment single run and selected advisor is: ' . $advisorId);
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
        if (!empty($tierR)) {
            $leads->where('tier_id', '!=', $tierR->id);
        }

        return $leads->get();
    }

    public function shouldProceed(): bool
    {
        $start_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $end_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));
        $shouldProceed = now()->between($start_time, $end_time) && ((int) config('constants.CAR_LEAD_ALLOCATION_MASTER_SWITCH') == 1);

        return $shouldProceed;
    }

    public function isLeadReassigned($lead)
    {
        $leadDetail = CarQuoteRequestDetail::where('car_quote_request_id', $lead->id)->first();
        if ($leadDetail && $leadDetail->advisor_assigned_date > now()->subMinutes(2)) {
            return true;
        } else {
            return false;
        }
    }

    public function updateLeadTier($lead, $tier): void
    {
        CarQuote::where('id', $lead->id)->update([
            'tier_id' => $tier->id,
        ]);

        info('Tier with name : ' . $tier->name . ' is assigned to car lead with uuid : ' . $lead->uuid);
    }

    public function fetchUsersOnAllocationCriteria($ruleUserIds, $teamId)
    {
        $excludedUserIds = $this->getExcludedUserIds($teamId);

        // Create a query to fetch lead allocations with their associated users.
        return LeadAllocation::with('leadAllocationUser')
            ->where(function ($query) {
                // Apply allocation count and max capacity conditions.
                $query->whereRaw('allocation_count < max_capacity')
                    ->orWhere('max_capacity', -1);
            })
            ->whereIn('user_id', $ruleUserIds)
            ->whereNotIn('user_id', $excludedUserIds)
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->orderBy('last_allocated')
            ->get()
            ->pluck('user_id')
            ->toArray();
    }
}
