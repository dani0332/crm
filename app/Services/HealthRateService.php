<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HealthRateService extends BaseService
{
    public function getList(Request $request): LengthAwarePaginator
    {
        $query = HealthRate::with('healthPlan', 'healthPlanCoPayment')
            ->select(
                'id',
                'health_plan_id',
                'health_plan_co_payment_id',
                'emirate_type',
                'cohort', 'gender',
                'marital_status',
                'min_age',
                'max_age',
                'premium',
                'status',
                'version',
                'is_active',
                'created_at',
                'updated_at',
            )
            ->where('health_plan_id', $request->id)
            ->where('status', strtolower($request->status ?? HealthPlanRateSheetStatusEnum::ACTIVE->value))
            ->orderByDesc('id');

        $perPage = (int) $request->input('per_page', 10);

        return $query->paginate($perPage);
    }

    public function getRateById(int $id): ?HealthRate
    {
        return HealthRate::with('healthPlan', 'healthPlanCoPayment')
            ->select(
                'id',
                'health_plan_id',
                'health_plan_co_payment_id',
                'emirate_type',
                'cohort', 'gender',
                'marital_status',
                'min_age',
                'max_age',
                'premium',
                'status',
                'version',
                'is_active',
                'created_at',
                'updated_at',
            )
            ->firstWhere('id', $id);
    }
}
