<?php

namespace App\Repositories;

use App\Enums\RuleTypeEnum;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CommercialKeyword;
use App\Models\Rule;

class RuleRepository extends BaseRepository
{
    public function model()
    {
        return Rule::class;
    }

    /**
     * get commercial rules
     *
     * @return mixed
     */
    public function fetchGetCommercialRule($lead)
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
                return $this->_getCommercialRule();

            }
        }
    }

    public function _getCommercialRule()
    {
        // Join relevant tables to retrieve commercial rules for car make and model.
        // Filter by rule type, ensure rules are active, and group by rule ID.
        // Select a concatenated list of user IDs as "leadSourceUsers" for each rule.
        return $this->model()::join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->join('users', 'users.id', 'rule_users.user_id')
            ->where('rule_type', RuleTypeEnum::CAR_MAKE_MODEL)
            ->where('rules.is_active', 1)
            ->groupBy('rule_details.rule_id')
            ->select(
                \DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers')
            )->get();
    }
}
