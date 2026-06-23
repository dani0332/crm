<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\BikePlanType;
use App\Enums\EaModelEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\RuleTypeEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TiersEnum;
use App\Enums\UserStatusEnum;
use App\Models\BikeQuote;
use App\Models\BikeQuotePlanDetail;
use App\Models\BikeQuoteRequestDetail;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CommercialKeyword;
use App\Models\InsuranceProvider;
use App\Models\LeadAllocation;
use App\Models\LeadSource;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteBatches;
use App\Models\Rule;
use App\Models\RuleLeadSource;
use App\Models\Team;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\User;
use App\Models\UserTeams;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BikeAllocationService extends AllocationService
{
    protected bool $hasNationalityConfig = false;
    protected array $advisorIDs = [];
    protected array $excludedAdvisorIds = [];
    protected $lead = null;

    protected function resetProps(): void
    {
        $this->hasNationalityConfig = false;
        $this->advisorIDs = [];
        $this->excludedAdvisorIds = [];
        $this->lead = null;
    }

    public function fetchLead($quoteId, $overrideAdvisorId)
    {
        // Check if Dubai Now exclusion should be applied
        $shouldIncludeDubaiNow = $this->getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;

        // List of exempted lead sources
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD];

        // Add Dubai Now to exempted lead sources if $shouldIncludeDubaiNow is true
        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        // Create a query to retrieve a bike lead based on the provided quote ID and filters.
        $bikeQuoteQuery = PersonalQuote::with('bikeQuote')
            ->where('uuid', $quoteId)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', $exemptedLeadSources);

        if (! $overrideAdvisorId) {
            $bikeQuoteQuery->where(function ($q) {
                $q->whereNull('advisor_id')
                    ->orWhere(function ($sq) {
                        $sq->where('source', LeadSourceEnum::EA_IMCRM)
                            ->where('ea_model', EaModelEnum::Collaborate->value)
                            ->whereNull('expert_advisor_id');
                    });
            });
        }

        return $bikeQuoteQuery->first();
    }

    public function getTier($tierId)
    {
        return Tier::where('id', $tierId)->first();
    }

    public function getTierBasedOnValue($bikeLead, $tiersQuery): void
    {
        if ($bikeLead->bikeQuote->model_detail_id == null) {
            $tiersQuery->where('name', TiersEnum::TIER_L)->first();
        } else {
            $valuations = $this->getValuation($bikeLead->bikeQuote->model_detail_id, $bikeLead->bikeQuote->year_of_manufacture);

            $axaProvider = InsuranceProvider::where('code', InsuranceProviderEnum::AXA->value)->first();

            $axaValuation = array_filter($valuations, function ($provider) use ($axaProvider) {
                return $provider->providerId == $axaProvider->id;
            });

            $bikeValue = 0;

            if (! empty($axaValuation)) {
                $firstAxaValuation = reset($axaValuation); // Get the first element of the array
                $bikeValue = $firstAxaValuation->bikeValue;
            }

            LoggerService::info('bike value as per valuation engine for GIG is '.$bikeValue);
            $tiersQuery->where('min_price', '<=', $bikeValue)->where('max_price', '>=', $bikeValue);
        }
    }

    public function getExcludedUserIds()
    {
        // Define a list of excluded team names.
        $excludedTeams = [TeamNameEnum::AFFINITY];

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

    public function getPlanAndYear($bikeLead): array
    {
        // Retrieve bike quote plans with specific conditions.
        $plans = BikeQuotePlanDetail::where('quote_uuid', $bikeLead->uuid)
            ->where('is_rating_available', true)
            ->where('repair_type', BikePlanType::COMP)->get();

        // Calculate the year of manufacture that is 15 years ago from the current date.
        $yearOfManufacture = now()->subYear(15)->year;

        LoggerService::info('yearOfManufacture is: '.$yearOfManufacture.' and number of plans found are: '.count($plans));

        return [$plans, $yearOfManufacture];
    }

    /**
     * @return array|mixed
     */
    public function executeRevivalCheck($leadSource, $tierUserIds): mixed
    {
        if ($leadSource == LeadSourceEnum::REVIVAL_REPLIED) {
            // if lead source is revival replied then we should only assign to organic advisors

            // Retrieve the ID of Organic team.
            $organicId = Team::whereIn('name', TeamNameEnum::ORGANIC)->select('id')->get();

            // Retrieve the user IDs associated with organic team.
            $organicUserIds = UserTeams::whereIn('team_id', $organicId)->select('user_id')->get();

            // Getting common to get only organic advisors
            $tierUserIds = array_intersect($tierUserIds, $organicUserIds);
        }

        return $tierUserIds;
    }

    protected function getDeferredLeads(): mixed
    {
        return BikeQuote::whereNull('advisor_id')->where('deferred', 1)->whereBetween('deferred_at', [now()->subDay(2)->toDateTimeString(), now()]);
    }

    public function getUserAgeInYears($dob)
    {
        try {
            $dateString = $dob;
            // Check if reformatting is needed (assuming d-M-Y format has month as a 3-letter abbreviation)
            if (strlen($dateString) === 11 && strpos($dateString, '-') !== false) {
                $dateParts = explode('-', $dateString);
                if (strlen($dateParts[1]) === 3) {  // Month is a 3-letter abbreviation
                    $reformattedDate = $dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0];
                    $userDob = Carbon::createFromFormat('Y-M-d', $reformattedDate);
                } else {
                    // Handle cases where the format isn't as expected (e.g., full month name)
                    $userDob = Carbon::createFromFormat('d-M-Y', $dateString);
                }
            } elseif (strpos($dateString, ':') !== false || strpos($dateString, ' ') !== false) {
                // Handle cases where time also included
                $userDob = Carbon::createFromFormat('Y-m-d H:i:s', $dateString);
            } else {
                // Assume it's already in a compatible format (e.g., Y-m-d)
                $userDob = Carbon::createFromFormat('Y-m-d', $dateString);
            }

            return $userDob->age;
        } catch (\Exception $e) {
            // Logging the error
            LoggerService::error("Failed to parse date in getUserAgeInYears method : $dateString", ['error' => $e->getMessage()]);

            return 0;
        }
    }

    public function findTier($bikeLead): ?Tier
    {
        if (empty($bikeLead->bikeQuote)) {
            LoggerService::error('Bike Quote is missing for lead ID: '.$bikeLead->id);

            return $this->fetchTierL($bikeLead->id, 'Bike Quote is missing');
        }

        $bikeQuote = $bikeLead->bikeQuote;
        $tier = null;

        // If model detail is missing, default to Tier L
        if (is_null($bikeQuote->model_detail_id)) {
            $tier = $this->fetchTierL($bikeLead->id, 'Model detail is missing');
        } else {
            // Fetch valuations based on model detail and year
            $valuations = $this->getValuation($bikeQuote->model_detail_id, $bikeQuote->year_of_manufacture);

            if (empty($valuations)) {
                $tier = $this->fetchTierL($bikeLead->id, 'No valuations found');
            } else {
                // Extract bike value from AXA valuation
                $bikeValue = $this->getBikeValueFromAxa($valuations);

                if ($bikeValue <= 0) {
                    $tier = $this->fetchTierL($bikeLead->id, 'AXA valuation missing or bike value is zero');
                } else {
                    // Determine tier based on bike value
                    $tier = $this->fetchTierByPrice($bikeValue, $bikeLead->id);
                }
            }
        }

        return $tier;
    }

    private function fetchTierL(int $leadId, string $reason): ?Tier
    {
        LoggerService::info($reason.'. Assigning default tier for lead ID: '.$leadId);

        return Tier::where('is_active', 1)
            ->where('name', TiersEnum::TIER_L)
            ->first();
    }

    private function fetchTierByPrice(float $bikeValue, int $leadId): ?Tier
    {
        LoggerService::info('Bike value from AXA valuation for lead ID '.$leadId.' is: '.$bikeValue);

        return Tier::where('is_active', 1)
            ->where('min_price', '<=', $bikeValue)
            ->where('max_price', '>=', $bikeValue)
            ->first();
    }

    private function getBikeValueFromAxa(array $valuations): float
    {
        $axaProviderId = InsuranceProvider::where('code', InsuranceProviderEnum::AXA->value)->value('id');

        if (empty($axaProviderId)) {
            LoggerService::info('AXA insurance provider not found.');

            return 0;
        }

        foreach ($valuations as $valuation) {
            if ($valuation->providerId == $axaProviderId) {
                return $valuation->bikeValue;
            }
        }

        return 0;
    }

    public function getEligibleUserForAllocation($tierId, $advisorId, $isReassignmentJob, $leadSource, $bikeLead)
    {
        $this->resetProps();
        $this->lead = $bikeLead;

        $tierUserIds = $this->getTierUserIds($tierId, $advisorId);
        LoggerService::info('Users against tierID '.$tierId.' are: '.json_encode($tierUserIds->toArray()));

        $tierUserIds = $this->executeRevivalCheck($leadSource, $tierUserIds);

        $this->resolveNationalityConfig($bikeLead);

        $eligibleUsers = $this->findEligibleUsers($tierUserIds, $advisorId, $isReassignmentJob);

        if (empty($eligibleUsers) && $this->hasNationalityConfig) {
            LoggerService::info(self::class.' - findEligibleUsers: No advisors found with nationality configuration, resetting nationality config and trying again');
            $this->resetNationalityConfig();
            $eligibleUsers = $this->findEligibleUsers($tierUserIds, $advisorId, $isReassignmentJob);
        }

        return $eligibleUsers;
    }

    public function getAdvisorsByStatus($status, $tierUserIds, $advisorId = null)
    {
        $excludedUserIds = $this->getExcludedUserIds();

        $excludedUserIds = $excludedUserIds ? $excludedUserIds->pluck('user_id')->toArray() : [];

        // Create a query to fetch lead allocations with their associated users.
        $query = LeadAllocation::with('leadAllocationUser')
            ->whereHas('leadAllocationUser', function ($query) use ($status) {
                $query->where('status', $status)->whereDoesntHave('roles', function ($query) {
                    $query->whereIn('name', [RolesEnum::CLIENTSUPPORT, RolesEnum::CLIENTSUPPORTLEAD]);
                });
            })
            ->where(function ($query) {
                // Apply allocation count and max capacity conditions.
                $query->whereRaw('allocation_count < max_capacity')
                    ->orWhere('max_capacity', -1);
            })
            ->whereIn('user_id', $tierUserIds)
            ->when(! empty($excludedUserIds), function ($query) use ($excludedUserIds) {
                $excludedUserIds = $this->finalizeExcludedAdvisorIds($excludedUserIds);
                $query->whereNotIn('user_id', $excludedUserIds);
            })
            ->where('quote_type_id', QuoteTypes::BIKE->id())
            ->activeUser()
            ->when(
                $this->hasNationalityConfig,
                fn ($q) => $q->whereIn('user_id', $this->advisorIDs),
                function ($q) {
                    if (! empty($this->excludedAdvisorIds)) {
                        $q->whereNotIn('user_id', $this->excludedAdvisorIds);
                    }
                },
            )
            ->when(
                $this->lead?->source === LeadSourceEnum::EA_IMCRM && $this->lead?->ea_model === EaModelEnum::Collaborate,
                fn ($q) => $q->whereHas('leadAllocationUser', fn ($uq) => $uq->whereHas('permissions', fn ($pq) => $pq->where('name', PermissionsEnum::AssignedExpertAdvisor)))
            )
            ->when(
                $this->lead?->source === LeadSourceEnum::EA_IMCRM && $this->lead?->ea_model === EaModelEnum::Referral,
                fn ($q) => $q->whereHas('leadAllocationUser', fn ($uq) => $uq->whereHas('permissions', fn ($pq) => $pq->where('name', PermissionsEnum::AssignedReferralAdvisor)))
            )
            ->orderBy('last_allocated');

        if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.' - getAdvisorsByStatus: EA_IMCRM lead, filtering advisor pool by permission', extra: [
                'ea_model' => $this->lead->ea_model?->value,
                'permission' => $this->lead->ea_model === EaModelEnum::Collaborate
                    ? PermissionsEnum::AssignedExpertAdvisor
                    : PermissionsEnum::AssignedReferralAdvisor,
            ]);
        }

        // Exclude a specific advisor if an advisor ID is provided.
        if (! empty($advisorId)) {
            $query->where('user_id', '!=', $advisorId);
        }

        // Return the resulting collection of advisors.
        return $query->get();
    }

    public function getRules($bikeLead)
    {
        if ($this->hasNationalityConfig) {
            return collect([]);
        }

        return $this->getRulesForLeadSource($bikeLead);
    }

    private function getRulesForLeadSource($lead)
    {
        // as we are dealing with the same table for car and bike, we take data from CarMake and CarModel
        $commercialKeywords = CommercialKeyword::select('id', 'name')->get();

        $commercialCarMake = CarMake::where('id', $lead->bikeQuote->make_id)
            ->where('is_commercial', true)
            ->select('id')
            ->first();

        $commercialCarModel = CarModel::where('id', $lead->bikeQuote->model_id)
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

        LoggerService::info('keyword not found and vehicle is not commercial as well, so checking for normal rules');

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
        // Join relevant tables to retrieve commercial rules for bike make and model.
        // Filter by rule type, ensure rules are active, and group by rule ID.
        // Select a concatenated list of user IDs as "leadSourceUsers" for each rule.
        return Rule::join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->join('users', 'users.id', 'rule_users.user_id')
            ->where('rule_type', RuleTypeEnum::CAR_MAKE_MODEL)
            ->where('rules.is_active', 1)
            ->groupBy('rule_details.rule_id')
            ->select(
                DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers')
            )->get();
    }

    public function determineFinalUserId($lead, $eligibleUsers, $rules): mixed
    {
        // Extract user IDs from the eligible user data and convert them to an array.
        $availableUserIds = collect($eligibleUsers)->pluck('user_id')->toArray();
        LoggerService::info('Available User IDs are: '.json_encode($availableUserIds));

        if ($this->hasNationalityConfig) {
            $finalEligibleUserIds = $availableUserIds;
        } else {
            if (count($rules) > 0) {
                // If there are rules, retrieve user IDs from the rule records.
                $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);

                LoggerService::info('Rule user IDs are: '.json_encode($ruleUserIds));

                // Find the intersection of available user IDs and rule user IDs.
                $finalEligibleUserIds = array_intersect($availableUserIds, $ruleUserIds);

                LoggerService::info('Rule found, and users against the rule are: '.json_encode($finalEligibleUserIds));
            } else {
                LoggerService::info('No rules found, so filtering rule users');

                // If no rules are found, get user IDs from rule lead sources.
                $ruleUsers = $this->getRuleUsers();

                LoggerService::info('Rule users are: '.json_encode($ruleUsers));

                $ruleUsers = $this->finalizeExcludedAdvisorIds($ruleUsers);

                LoggerService::info('Final rule users after excluding non rule users: '.json_encode($ruleUsers));

                // Find the difference between available user IDs and rule users.
                $finalEligibleUserIds = array_diff($availableUserIds, $ruleUsers);

                LoggerService::info('Final login and available users after rule exclusion are: '.json_encode($finalEligibleUserIds));
            }
        }

        $finalEligibleUserIds = $this->fetchOnlyBikeEligibleAdvisors($finalEligibleUserIds);

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
        LoggerService::info('About to assign bike lead to user with ID: '.$userId);

        $isEACollaborate = $lead->source === LeadSourceEnum::EA_IMCRM
            && $lead->ea_model === EaModelEnum::Collaborate;

        if ($lead->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.' - processLeadAssignment: EA_IMCRM lead assignment', extra: [
                'uuid' => $lead->uuid ?? null,
                'ea_model' => $lead->ea_model?->value,
                'assigning_to' => $isEACollaborate ? 'expert_advisor_id' : 'advisor_id',
                'advisor_id' => $userId,
            ]);
        }

        // Store the previous Assignment Type
        $previousAssignmentType = $lead->assignment_type;

        // Store the previous advisor ID.
        $previousUserId = $lead->advisor_id;

        // Assign the lead to the user and get the associated quote.
        $bikeQuote = $this->assignLeadToUserAndGetQuote($lead, $userId, $tier, $assignmentType);

        LoggerService::info('Advisor and tier assignment completed to user with ID: '.$userId.' and tier name: '.$tier->name);

        // Update the bike lead detail record and store the previous advisor assigned date.
        $previousAdvisorAssignedDate = $this->updateBikeLeadDetailRecord($bikeQuote->id);

        LoggerService::info('Updating user record in lead allocation table with count increment for User ID: '.$userId);

        // Depending on the assignment type, either add or adjust allocation counts.
        if (! $isEACollaborate) {
            $assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED ? $this->addAllocationCounts($userId, QuoteTypes::BIKE->id()) : $this->adjustAllocationCounts($userId, $lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, QuoteTypes::BIKE->id());
        }

        LoggerService::info('Completed assignment of lead, and lead count update is done for quote with code: '.$bikeQuote->code);

        $this->resetProps();
    }

    private function assignLeadToUserAndGetQuote($lead, $userId, $tier, $assignmentType): mixed
    {
        // Assign the lead to the advisor and send an email
        // Check if the lead was previously assigned to an advisor and log the change.
        if (! empty($lead->advisor_id)) {
            LoggerService::info('Was previously assigned to User ID: '.$lead->advisor_id.' and is now being assigned to User ID: '.$userId);
        }

        $isEACollaborate = $lead->source === LeadSourceEnum::EA_IMCRM
            && $lead->ea_model === EaModelEnum::Collaborate;

        // Update lead properties.
        $lead->tier_id = $tier->id;
        $lead->cost_per_lead = $tier->cost_per_lead;
        $lead->auto_assigned = true;
        if ($isEACollaborate) {
            LoggerService::info(self::class.' - assignLeadToUserAndGetQuote: EA Collaborate model, assigning to expert_advisor_id', extra: [
                'expert_advisor_id' => $userId,
                'ea_model' => $lead->ea_model?->value,
            ]);
            $lead->expert_advisor_id = $userId;
        } else {
            $lead->advisor_id = $userId;
            $lead->assignment_type = $assignmentType;
        }

        LoggerService::info(self::class.' - assignLeadToUserAndGetQuote: Checking lead_assignment_trigger', extra: [
            'current_value' => $lead->lead_assignment_trigger ?? 'null',
        ]);
        if (empty($lead->lead_assignment_trigger)) {
            LoggerService::info(self::class.' - assignLeadToUserAndGetQuote: Setting lead_assignment_trigger to LEAD_AUTO_ASSIGNED');
            $lead->lead_assignment_trigger = LeadAssignmentTriggerEnum::LEAD_AUTO_ASSIGNED;
        }
        // Get the latest quote batch and assign it to the lead.
        $quoteBatch = QuoteBatches::latest()->first();
        $lead->quote_batch_id = $quoteBatch->id;

        // Log information about the quote batch assignment.
        LoggerService::info('About to assign Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);

        // Save the updated lead.
        $lead->save();

        $lead->endAllocation();

        return $lead;
    }

    public function updateBikeLeadDetailRecord($leadId)
    {
        // Log information about the update operation.
        LoggerService::info('About to update personal quote detail record for lead ID: '.$leadId);

        // Attempt to find an existing bike quote detail record for the given lead.
        $personalQuoteDetail = PersonalQuoteDetail::where('personal_quote_id', $leadId)->first();

        // Initialize a variable to store the old advisor assigned date.
        $oldAdvisorAssignedDate = $personalQuoteDetail->advisor_assigned_date ?? '';

        $this->upsertQuoteDetail($leadId, PersonalQuoteDetail::class, 'personal_quote_id');

        // Return the old advisor assigned date, if applicable.
        return $oldAdvisorAssignedDate;
    }

    public function fetchLeadsForReAssignment($advisorId)
    {
        // Calculate the start date for lead retrieval
        $from = now()->subDay()->setTime(12, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        LoggerService::info('Leads will be picked up in reassignment from : '.$from.' until : '.now()->toDateTimeString());

        // Check if Dubai Now exclusion should be applied
        $shouldIncludeDubaiNow = $this->getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;

        // List of exempted lead sources
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD];

        // Add Dubai Now to exempted lead sources if $shouldIncludeDubaiNow is true
        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        // Get the Tier R
        $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

        // Query to fetch leads
        $leads = PersonalQuote::with('bikeQuote')
            ->whereBetween('created_at', [$from, now()])
            ->whereNotIn('source', $exemptedLeadSources)
            ->where('quote_status_id', QuoteStatusEnum::NewLead)
            ->where('quote_type_id', QuoteTypeId::Bike);

        // Filter by advisor ID if provided , which mean reassignment is going to run for a single advisor
        if ($advisorId != 0) {
            $leads->where('advisor_id', $advisorId);
            LoggerService::info('Inside reassignment single run and selected advisor is: '.$advisorId);
        } else {
            // If advisor ID is not provided, get unavailable advisors and filter leads by them
            $advisors = $this->getUnavailableAdvisor();
            if (count($advisors) > 0) {
                $advisorIds = $advisors->pluck('user_id');
                $leads->whereIn('advisor_id', $advisorIds);
            }
        }

        // Filter leads by tier (if applicable)
        if (! empty($tierR)) {
            LoggerService::info('Inside Tier Condition for reassignment job and selected tier is: '.$tierR->name.' with Tier Id: '.$tierR->id);
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
        $leadDetail = BikeQuoteRequestDetail::where('car_quote_request_id', $lead->id)->first();
        if ($leadDetail && $leadDetail->advisor_assigned_date > now()->subMinutes(2)) {
            return true;
        } else {
            return false;
        }
    }

    public function updateLeadTier($lead, $tier): void
    {
        PersonalQuote::where('id', $lead->id)->update([
            'tier_id' => $tier->id,
        ]);

        LoggerService::info('Tier with name : '.$tier->name.' is assigned');
    }

    public function fetchOnlyBikeEligibleAdvisors($userIds)
    {
        LoggerService::info('Fetching only eligible bike advisors');
        $allowed_teams = [TeamNameEnum::BIKE, TeamNameEnum::Bike_Team];
        $bike_advisor_roles = [RolesEnum::BikeAdvisor, RolesEnum::BikeManager];
        $finalEligibleUserIds = array_filter($userIds, function ($userId) use ($bike_advisor_roles, $allowed_teams) {
            $userRolesAndTeams = checkForRoleOrTeam($userId, 'both');  // Fetch roles and teams in one go
            $roles = $userRolesAndTeams['roles'];
            $teams = $userRolesAndTeams['teams'];

            // Check for matching roles
            $matchingRoles = collect($roles)->pluck('name')->intersect($bike_advisor_roles);

            // Check for matching teams
            $isValidTeam = isValidTeamForLOBAdvisor($teams, $allowed_teams);

            return $matchingRoles->isNotEmpty() && $isValidTeam;
        });
        LoggerService::info('Final eligible users after filtering for bike advisors: '.json_encode($finalEligibleUserIds));

        return $finalEligibleUserIds;
    }

    private function resolveNationalityConfig($lead)
    {
        $config = NationalityAllocationService::find(QuoteTypes::BIKE, $lead->nationality_id);

        if ($config) {
            $this->hasNationalityConfig = true;
            $this->advisorIDs = NationalityAllocationService::getUserIDs($config);
            LoggerService::info(self::class." - Nationality Config found for Nationality ID: {$lead->nationality_id} | Advisor IDs: ".implode(', ', $this->advisorIDs));
        } else {
            $this->resolveExcludedAdvisorIds();
        }

        return $config;
    }

    private function resolveExcludedAdvisorIds()
    {
        $excludedAdvisorIds = NationalityAllocationService::getExcludedUserIds(QuoteTypes::BIKE);

        if (empty($excludedAdvisorIds)) {
            return;
        }

        $excludedAdvisorIds = $this->finalizeExcludedAdvisorIds($excludedAdvisorIds);

        $this->excludedAdvisorIds = $excludedAdvisorIds;
    }

    private function findEligibleUsers($tierUserIds, $advisorId, $isReassignmentJob)
    {
        // Define the order in which user statuses should be considered.
        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        // Iterate through user statuses in the specified order.
        foreach ($statusOrder as $status) {
            // Get eligible users with the specified status.
            $eligibleUsers = $this->getAdvisorsByStatus($status, $tierUserIds, $advisorId);

            // If eligible users are found, log the results and return them.
            if ($eligibleUsers && count($eligibleUsers) > 0) {
                LoggerService::info('Fetching Users with the availability status of: '.UserStatusEnum::getUserStatusText($status));

                return $eligibleUsers->toArray();
            }
            LoggerService::info('No Users were found with the availability status of: '.UserStatusEnum::getUserStatusText($status));
        }

        // If no eligible users are found, return an empty array.
        return [];
    }

    private function resetNationalityConfig()
    {
        LoggerService::info(self::class.' - resetNationalityConfig: Resetting nationality config');
        $this->resetProps();

        $this->resolveExcludedAdvisorIds();
    }

    protected function finalizeExcludedAdvisorIds(?array $excludedAdvisorIds): array
    {
        if (empty($excludedAdvisorIds)) {
            return [];
        }

        $superAdvisorIds = User::whereHas('permissions', function ($query) {
            $query->where('name', PermissionsEnum::NONRULE_LEADALLOCATION);
        })->pluck('id')->toArray();

        $excludedAdvisorIds = array_diff($excludedAdvisorIds, $superAdvisorIds);
        $excludedAdvisorIds = array_values($excludedAdvisorIds);

        return $excludedAdvisorIds;
    }
}
