<?php

namespace App\Repositories;

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
        $_return = false;
        $commercialCarModel = CarModel::where('id', $lead->car_model_id)
            ->where('is_commercial', true)
            ->count();

        if ($commercialCarModel) {
            $_return = true;
        }

        $commercialKeywords = CommercialKeyword::select('id', 'name')->get();
        $commercialKeywordsCheck = in_array(strtolower(trim($lead->full_name)), array_column($commercialKeywords->toArray(), strtolower(trim('name'))));
        if ($commercialKeywordsCheck) {
            $_return = true;
        }

        return $_return;
    }

}
