<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Enums\RuleTypeEnum;
use App\Models\LeadSource;
use App\Models\Rule;
use App\Models\RuleDetail;
use App\Models\RuleUser;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class RuleService extends BaseService
{
    protected $query;
    protected $searchPrefix = 'r.';
    public function __construct()
    {
        $this->query =

        DB::table('rules as r')
            ->select(
                'r.id',
                'r.name',
                'r.rule_start_date',
                'r.rule_end_date',
                'rt.name as rule_type',
                'rd.lead_source_id as pid',
                'rd.car_make_id as car_make_id',
                'rd.car_model_id as car_model_id',
                'ls.name as lead_source_id',
                DB::raw('group_concat(distinct(u.name)) AS rule_users'),
                'r.is_active',
                'r.updated_at',
                'r.created_at'
            )
            ->leftJoin('rule_details as rd', 'rd.rule_id', 'r.id')
            ->leftJoin('rule_users as ru', 'ru.rule_id', 'r.id')
            ->leftJoin('lead_sources as ls', 'ls.id', 'rd.lead_source_id')
            ->leftJoin('rule_types as rt', 'rt.id', 'r.rule_type')
            ->leftJoin('users as u', 'u.id', 'ru.user_id')
            ->groupBy('r.id');
    }

    public function getEntity($id)
    {
        return $this->query->where($this->searchPrefix.'id', $id)->first();
    }

    public function getGridData($model, $request)
    {
        $this->query = addSearchClauses($model, $request, $this->query, $this->searchPrefix);
        $this->query = addOrderByClauses($request, $this->query, $this->searchPrefix);

        return $this->query;
    }

    public function saveRule(Request $request)
    {
        if (isset($request->name) && isset($request->lead_source_id)) {
            $existingRuleLeadSource = RuleDetail::where('lead_source_id', $request->lead_source_id)->get();
            if (count($existingRuleLeadSource) > 0) {
                $errorResponse = new stdClass;
                $errorResponse->message = 'Error: Rule against same Lead Source already exists';

                return $errorResponse;
            }
        } elseif (isset($request->name) && isset($request->rule_type)
            && $request->rule_type == RuleTypeEnum::CAR_MAKE_MODEL) {
            $existingCommercialRule = Rule::where('rule_type', $request->rule_type)
                ->get();

            if (count($existingCommercialRule) > 0) {
                $errorResponse = new stdClass;
                $errorResponse->message = 'Error: Commercial rule already exist';

                return $errorResponse;
            }
        }

        $rule = Rule::create([
            'name' => $request->name,
            'rule_start_date' => $request->rule_start_date,
            'rule_end_date' => $request->rule_end_date,
            'is_active' => $request->has('is_active') && $request->is_active == 'on' ? 1 : 0,
            'is_applicable_for_rules' => false,
            'rule_type' => $request->get('rule_type'),
        ]);

        $userIds = $request->rule_users;

        $rule->ruleDetail()->create([
            'lead_source_id' => $request->get('lead_source_id'),
        ]);

        $rule->ruleUsers()->attach($userIds);

        return $rule;
    }

    public function updateRule(Request $request, $id)
    {
        $rule = Rule::where('id', $id)->first();
        $rule->name = $request->name;
        if (isset($request->rule_start_date)) {
            $rule->rule_start_date = $request->rule_start_date;
        }
        if (isset($request->rule_end_date)) {
            $rule->rule_end_date = $request->rule_end_date;
        }

        $rule->is_active = $request->has('is_active') && $request->is_active == 'on' ? 1 : 0;
        $rule->save();

        $userIds = $request->rule_users;

        $rule->ruleDetail()->update([
            'lead_source_id' => $request->get('lead_source_id'),
        ]);

        if (isset($request->rule_users)) {
            RuleUser::where('rule_id', $rule->id)->delete();
            $rule->ruleUsers()->attach($userIds);
        }

        LoggerService::info('------ Rule update is successfully done by user : '.auth()->user()->id.' for rule : '.$rule->name.' ------');

        return $rule;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'name' => 'input|title|required|likeSearch',
            'rule_start_date' => 'input|date|title',
            'rule_end_date' => 'input|date|title',
            'rule_type' => 'select|required',
            'lead_source_id' => 'select|title|required_if:rule_type,1',
            'rule_users' => 'select|multiple|required|multiSearch',
            'is_active' => 'input|checkbox|title',
            'created_at' => 'input|title|date|range|dateRange',
            'updated_at' => 'input|date',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'name':
                $title = 'Rule Name';
                break;
            case 'rule_start_date':
                $title = 'Rule Start Date';
                break;
            case 'rule_end_date':
                $title = 'Rule End Date';
                break;
            case 'lead_source_id':
                $title = 'Lead Source';
                break;
            case 'rule_users':
                $title = 'Rule Users';
                break;
            case 'is_active':
                $title = 'Is Active ?';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Updated Date';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'created_at,updated_at,rule_start_date,rule_end_date',
            'list' => 'rule_start_date,rule_end_date,updated_at',
            'update' => 'id,created_at,updated_at,rule_start_date,rule_end_date',
            'show' => 'id,rule_start_date,rule_end_date',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['name', 'created_at'];
    }

    public function fillSortingProperties()
    {
        return ['id', 'name'];
    }

    public function getUsersByLeadSourceRules(string $leadSource, $quoteTypeId = null)
    {
        return LeadSource::query()
            ->leftJoin('rule_details', 'rule_details.lead_source_id', 'lead_sources.id')
            ->join('rules', 'rules.id', 'rule_details.rule_id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->join('users', 'users.id', 'rule_users.user_id')
            ->where('rules.quote_type_id', (int) $quoteTypeId)
            ->where('lead_sources.name', $leadSource)
            ->where('rules.is_active', true)
            ->where('lead_sources.is_applicable_for_rules', true)
            ->groupBy('rule_details.lead_source_id')
            ->select([
                'lead_sources.name AS leadSourceName',
                'lead_sources.id AS leadSourceId',
                DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers'),
            ])
            ->get();

    }

    public function getUserIdsFromRuleRecords($matchedRuleRecords): array
    {
        $leadSourceUsers = $matchedRuleRecords->first()->leadSourceUsers;
        if (str_contains($leadSourceUsers, ',')) {
            $userIds = array_map('intval', explode(',', $leadSourceUsers));
        } else {
            $userIds = [(int) $leadSourceUsers];
        }

        return $userIds;
    }

    public function getEmailsByLeadSource($leadSource, $quoteTypeId)
    {
        $rules = app(RuleService::class)->getUsersByLeadSourceRules($leadSource, $quoteTypeId);
        if (count($rules) > 0) {
            $userIds = app(RuleService::class)->getUserIdsFromRuleRecords($rules);
            $emails = User::whereIn('id', $userIds)->pluck('email')->toArray();

            return $emails;
        }

        return [];
    }
    public function getFicRulesUsers()
    {
        return Rule::join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->where('rules.is_active', 1)
            ->where('rules.rule_type', RuleTypeEnum::FIC)
            ->distinct()
            ->pluck('rule_users.user_id')
            ->toArray();
    }

    public function getRuleUserIds(QuoteTypes $quoteType, bool $excludeVehicleUseRule = false): mixed
    {
        return Rule::join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->where('rules.quote_type_id', $quoteType->id())
            ->where('rules.is_active', 1)
            ->distinct()
            ->when($quoteType === QuoteTypes::CAR && $excludeVehicleUseRule, function ($query) {
                $query->where('rule_type', '!=', RuleTypeEnum::VEHICLE_USE);
            })
            ->pluck('rule_users.user_id')
            ->toArray();
    }
}
