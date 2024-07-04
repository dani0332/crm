<?php

namespace App\Services;

use Exception;
use App\Enums\EnvEnum;
use App\Models\EmailActivity;
use App\Models\SICHealthConfig;
use Illuminate\Support\Facades\Log;

class SICHealthConfigService extends BaseService
{


    public function getEntity()
    {
        $data = SICHealthConfig::latest()->first();

        return SICHealthConfig::latest()->first();
    }

    public function saveEntity($id, $data){
    // try {
            $sicHealthConfig = SICHealthConfig::where("id", $id)->first();

            if(!$sicHealthConfig){
               return SICHealthConfig::create([
                    'is_age' => $data->is_age,
                    'min_age' => $data->min_age,
                    'max_age' => $data->max_age,
                    'is_type' => $data->is_type,
                    'plan_types' => $data->plan_types,
                    'is_nationality' => $data->is_nationality,
                    'nationalities' => $data->nationalities,
                    'is_member_category' => $data->is_member_category,
                    'member_categories' => $data->member_categories,
                 ]);

            }
            else {

                $sicHealthConfig->is_age = $data->is_age;
                $sicHealthConfig->min_age = $data->min_age;
                $sicHealthConfig->max_age = $data->max_age;
                $sicHealthConfig->is_type = $data->is_type;
                $sicHealthConfig->plan_types = $data->plan_types;
                $sicHealthConfig->is_nationality = $data->is_nationality;
                $sicHealthConfig->nationalities = $data->nationalities;
                $sicHealthConfig->is_member_category = $data->is_member_category;
                $sicHealthConfig->member_categories = $data->member_categories;
                $sicHealthConfig->save();
                return $sicHealthConfig;
            }

    //  }catch (Exception $e) {
        Log::error('SIC Health Config Error: '.$e->getMessage());
    //  }
    }


}
