<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HealthPlanService extends BaseService
{
    public function getPlanByCode($code): ?HealthPlan
    {
        return HealthPlan::select('id', 'code', 'cohort_enabled', 'gender_enabled', 'marital_status_enabled')
            ->firstWhere('code', $code);
    }

    public function getPlanById(int $id): ?HealthPlan
    {
        return HealthPlan::select('id', 'code', 'text', 'text_ar', 'provider_id', 'health_business_type',
            'plan_type_id', 'health_rating_eligibility_id', 'health_network_id', 'maf_link', 'is_hidden', 'is_active',
            'status', 'version', 'cohort_enabled', 'gender_enabled', 'marital_status_enabled', 'created_at', 'updated_at', 'deleted_at')
            ->firstWhere('id', $id);
    }

    public function getList(Request $request): LengthAwarePaginator
    {
        // Apply pagination if 'per_page' is set in the request, otherwise return all results
        $query = HealthPlan::with('insuranceProvider', 'healthNetwork', 'healthRatingEligibility', 'healthPlanType')
            ->select(
                'id', 'code', 'text', 'text_ar', 'provider_id', 'health_business_type',
                'plan_type_id', 'health_rating_eligibility_id', 'health_network_id', 'is_hidden', 'is_active',
                'status', 'version', 'created_at', 'updated_at', 'deleted_at'
            )
            ->where('status', strtolower($request->status ?? HealthPlanRateSheetStatusEnum::ACTIVE->value))
            ->when($request->provider_id, function ($query) use ($request) {
                $query->where('provider_id', $request->provider_id);
            })
            ->when($request->code, function ($query) use ($request) {
                $query->where('code', 'like', '%'.$request->code.'%');
            })
            ->orderByDesc('id');

        $perPage = (int) $request->get('per_page', 10);

        return $query->paginate($perPage);
    }

    public function create(array $data): HealthPlan
    {
        return HealthPlan::create($data);
    }

}
