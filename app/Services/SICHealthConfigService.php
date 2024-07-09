<?php

namespace App\Services;

use App\Models\SICHealthConfig;
use Exception;
use Illuminate\Support\Facades\Log;

class SICHealthConfigService extends BaseService
{
    public function getEntity()
    {
        return SICHealthConfig::latest()->first();
    }

    public function saveEntity($id,$data)
    {
        try {
        $sicHealthConfig = SICHealthConfig::where('id', $id)->first();
        $sicHealthConfig = SICHealthConfig::updateOrCreate(
            ['id' => $sicHealthConfig->id ?? null],
            [
                'is_age' => $data['is_age'],
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'is_type' => $data['is_type'],
                'plan_types' => $data['plan_types'],
                'is_nationality' => $data['is_nationality'],
                'nationalities' => $data['nationalities'],
                'is_member_category' => $data['is_member_category'],
                'member_categories' => $data['member_categories'],
            ]
        );

        return $sicHealthConfig;
         }catch (Exception $e) {
            Log::error('SIC Health Config Error: '.$e->getMessage());
         }
    }

}
