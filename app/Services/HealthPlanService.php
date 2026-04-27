<?php

namespace App\Services;

use App\Models\HealthPlan;

class HealthPlanService extends BaseService
{
    public function getPlanByCode($code): ?HealthPlan
    {
        return HealthPlan::select('id', 'code', 'cohort_enabled', 'gender_enabled', 'marital_status_enabled')
            ->firstWhere('code', $code);
    }

    public function getPlanById($id): ?HealthPlan
    {
        return HealthPlan::select('id', 'code', 'text', 'text_ar', 'provider_id', 'health_business_type',
            'plan_type_id', 'health_rating_eligibility_id', 'health_network_id', 'maf_link', 'is_hidden', 'is_active',
            'created_at', 'updated_at', 'deleted_at')
            ->find($id);
    }
}
