<?php

namespace App\Services;

use App\Models\HealthPlan;

class HealthPlanService extends BaseService
{
    public function getPlanByCode($code): ?HealthPlan
    {
        return HealthPlan::select('code', 'cohort_enabled', 'gender_enabled', 'marital_status_enabled')->firstWhere('code', $code);
    }
}
