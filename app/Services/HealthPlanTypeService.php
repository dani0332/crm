<?php

namespace App\Services;

use App\Models\HealthPlanType;
use Illuminate\Database\Eloquent\Collection;

class HealthPlanTypeService
{
    public function getByEmirateId(int $emirateId): Collection
    {
        return HealthPlanType::join('health_plan_type_emirate_price_range_mapping as map', 'map.health_plan_type_id', '=', 'health_plan_type.id')
            ->where('health_plan_type.is_active', 1)
            ->where('map.emirate_id', $emirateId)
            ->select('health_plan_type.id', 'health_plan_type.text')
            ->get();
    }

    public function getById(int $id): ?string
    {
        return HealthPlanType::find($id)?->text;
    }
}
