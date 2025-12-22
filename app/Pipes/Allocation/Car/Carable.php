<?php

namespace App\Pipes\Allocation\Car;

use App\Models\Rule;
use App\Enums\RuleEnum;
use App\Models\CarMake;
use App\Enums\RolesEnum;
use App\Models\CarModel;
use App\Enums\QuoteTypes;
use App\Models\LeadSource;
use App\Enums\RuleTypeEnum;
use App\Enums\CarVehicleUse;
use App\Enums\LeadSourceEnum;
use App\Models\LeadAllocation;
use App\Models\CommercialKeyword;
use App\Enums\CarRegistrationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\Logger\LoggerService;

trait Carable
{
    public function getBaseQuery($status, $userIds)
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
                fn ($q) => $q->whereIn('user_id', $this->allocationRequest->getAdvisorIDs()),
                function ($q) {
                    if ($this->allocationRequest->hasExcludedAdvisorIds()) {
                        $q->whereNotIn('user_id', $this->allocationRequest->getExcludedAdvisorIds());
                    }
                },
            )
            ->activeUser()
            ->when($this->allocationRequest->getReAssigFromAdvisorId(), fn ($q) => $q->where('user_id', '!=', $this->allocationRequest->getReAssigFromAdvisorId()));
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
