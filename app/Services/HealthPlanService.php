<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use Illuminate\Database\Eloquent\Collection;

class HealthPlanService extends BaseService
{
    public function getPlanByCode($code): ?HealthPlan
    {
        return HealthPlan::select('id', 'code', 'cohort_enabled', 'gender_enabled', 'marital_status_enabled')
            ->firstWhere('code', $code);
    }

    public function getPlanByIdStatus(int $id, string $status): Collection
    {
        return HealthPlan::select('id', 'code', 'text', 'text_ar', 'provider_id', 'health_business_type',
            'plan_type_id', 'health_rating_eligibility_id', 'health_network_id', 'maf_link', 'is_hidden', 'is_active',
            'status', 'version', 'cohort_enabled', 'gender_enabled', 'marital_status_enabled', 'created_at', 'updated_at', 'deleted_at')
            ->where('status', strtolower($status))
            ->where('id', $id)
            ->orderByDesc('id')
            ->get(); // It can be multiple records in case of archive status so its collection
    }

    public function getList(?string $status = null): Collection
    {
        return HealthPlan::select('id', 'code', 'text', 'text_ar', 'provider_id', 'health_business_type',
            'plan_type_id', 'health_rating_eligibility_id', 'health_network_id', 'is_hidden', 'is_active',
            'status', 'version', 'created_at', 'updated_at', 'deleted_at')
            ->where('status', strtolower($status ?? HealthPlanRateSheetStatusEnum::ACTIVE))
            ->orderByDesc('id')
            ->get(); // It can b
    }
}
