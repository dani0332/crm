<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\CarRegistrationType;
use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CommercialKeyword;
use App\Models\LeadAllocation;
use App\Models\LeadSource;
use App\Models\Rule;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait Carable
{
    /**
     * Normalize lead source by trimming special characters
     */
    private function normalizeLeadSource(string $source): string
    {
        return trim($source, " \t\n\r\0\x0B/?");
    }

    public function getBaseQuery($status, $userIds)
    {
        $excludedUserIds = $this->allocationRequest->get('excludedUserIds') ?? [];

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
                fn ($q) => $q->whereIn('user_id', $this->allocationRequest->getAdvisorIDs()),
                function ($q) {
                    if ($this->allocationRequest->hasExcludedAdvisorIds()) {
                        $q->whereNotIn('user_id', $this->allocationRequest->getExcludedAdvisorIds());
                    }
                },
            )
            ->activeUser()
            ->when($this->allocationRequest->getReAssigFromAdvisorId(), fn ($q) => $q->where('user_id', '!=', $this->allocationRequest->getReAssigFromAdvisorId()))
            ->when(
                $this->lead?->source === LeadSourceEnum::EA_IMCRM && $this->lead?->ea_model === EaModelEnum::Referral,
                fn ($q) => $q->whereHas('leadAllocationUser', fn ($uq) => $uq->whereHas('permissions', fn ($pq) => $pq->where('name', PermissionsEnum::AssignedReferralAdvisor)))
            );
    }

    private function getRulesForLeadSource($lead)
    {
        $commercialKeywords = Cache::remember('all_commercial_keywords', now()->addHour(), function () {
            return CommercialKeyword::select('id', 'name')->get();
        });

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

                    return $this->getCommercialRule();
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
            $ruleId = $this->findLeadSourceRuleId($lead);

            $records = LeadSource::leftJoin('rule_details', 'rule_details.lead_source_id', 'lead_sources.id')
                ->join('rules', 'rules.id', 'rule_details.rule_id')
                ->join('rule_users', 'rule_users.rule_id', 'rules.id')
                ->join('users', 'users.id', 'rule_users.user_id')
                ->groupBy('rule_details.lead_source_id')
                ->where('rules.id', $ruleId)
                ->select(
                    'lead_sources.name AS leadSourceName',
                    'lead_sources.id AS leadSourceId',
                    DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers')
                )->get();

            return $records;
        }
    }

    private function findLeadSourceRuleId($lead)
    {
        $normalizedLeadSource = $this->normalizeLeadSource($lead->source);

        $rules = Rule::select('rules.id', 'rules.name', 'lead_sources.name as leadSourceName', 'rule_details.utm_campaign')
            ->join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('lead_sources', 'lead_sources.id', 'rule_details.lead_source_id')
            ->where('lead_sources.is_applicable_for_rules', 1)
            ->where('rules.is_active', 1)
            ->where('rules.rule_type', RuleTypeEnum::LEAD_SOURCE)
            ->whereRaw(
                'TRIM(BOTH ? FROM TRIM(BOTH ? FROM TRIM(BOTH ? FROM lead_sources.name))) = ?',
                ['/', '?', ' ', $normalizedLeadSource]
            )
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->get();

        if ($rules->isEmpty()) {
            return null;
        }

        $hasUtmCampaignRule = $rules->filter(fn ($rule) => ! empty($rule->utm_campaign))->isNotEmpty();

        /**
         * if there is no utm campaign rule, then return rule id without utm campaign
         * so lead can pick that rule which doesn't have utm campaign and just lead source
         */
        if (! $hasUtmCampaignRule) {
            $ruleWithoutUtmCampaign = $rules->first();
            LoggerService::info("There is only one Rule without UTM Campaigns having just lead source rules against source {$lead->source} so applying that main rule named: {$ruleWithoutUtmCampaign->name}");

            return $ruleWithoutUtmCampaign->id;
        }

        $utmCampaign = $lead->carQuoteRequestDetail->utm_campaign;

        /**
         * if utm campaign is empty for the lead, then return rule id without utm campaign
         * so lead can pick that rule which doesn't have utm campaign and just lead source
         */
        if (empty($utmCampaign)) {
            $ruleWithoutUtmCampaign = $rules->filter(fn ($rule) => empty($rule->utm_campaign))->first();

            if (! $ruleWithoutUtmCampaign) {
                LoggerService::info("No rule without UTM Campaign found for lead source '{$lead->source}' so no rule should be applied for this lead");

                return null;
            }

            LoggerService::info("UTM Campaign is empty for lead source '{$lead->source}' so applying rule without UTM Campaign named: {$ruleWithoutUtmCampaign->name}");

            return $ruleWithoutUtmCampaign->id;
        }

        /**
         * if utm campaign is not empty for the lead, then return rule id with utm campaign
         * so lead can pick that rule which has utm campaign along with lead source
         */
        $campaignRule = $rules->where('utm_campaign', $utmCampaign)->first();

        if (! $campaignRule) {
            LoggerService::info("No UTM Campaign rule found for lead with campaign '{$utmCampaign}' and source '{$lead->source}' so no rule should be applied for this lead");

            return null;
        }

        LoggerService::info("UTM Campaign rule found for lead with campaign '{$utmCampaign}' and source '{$lead->source}' so applying that rule named: {$campaignRule->name}");

        return $campaignRule->id;
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
        LoggerService::info(self::class." - Lead is {$lead->vehicle_use} use, applying company use rules for lead with Ref-ID: {$lead->uuid} ");

        return $this->getCompanyUsageRules($lead, RuleEnum::COMPANY_USE->value);

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
