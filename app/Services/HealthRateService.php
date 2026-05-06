<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class HealthRateService extends BaseService
{
    public function __construct(private HealthPlanService $healthPlanService) {}
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

    public function create(array $data)
    {
        $ids = [$data['health_plan_id']];
        // Fetch parent active plan (if exists)
        // We have to check against active and draft plan rates only
        $parentPlan = $this->healthPlanService->getPlanByParentIdStatus($data['health_plan_id'], HealthPlanRateSheetStatusEnum::ACTIVE->value);

        // If found, get id
        if ($parentPlan) {
            $ids[] = $parentPlan->id;
        }

        // Get draft rate against plan ids
        $draftRates = HealthRate::whereIn('health_plan_id', $ids)
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->get();

        // If not found, add
        if ($draftRates->isEmpty()) {
            DB::transaction(function () {
                // Add health rates control (rate sheet)
                HealthRateControl::create([]);

                // Add health rate
                HealthRate::create([]);
            });
        }

    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // Fetch health rate control id
            $rate = HealthRate::select('health_rate_control_id')->find($id);
            $healthRateControlId = $rate->health_rate_control_id;

            HealthRate::destroy($id);

            // After deleteing check if there is any rate exists with the same health rate control id
            $rates = HealthRate::where('health_rate_control_id', $healthRateControlId)->count();

            // If not, delete the health rate control
            if ($rates == 0) {
                HealthRateControl::destroy($healthRateControlId);
            }
        });
    }
}
