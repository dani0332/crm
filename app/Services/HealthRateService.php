<?php

namespace App\Services;

use App\Enums\GenderEnum;
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
        // Fetch parent active plan (if exists)
        // We have to check against active and draft plan rates only
        $planIds = $this->healthPlanService->getRelatedPlanIds($data['health_plan_id']);

        // Get draft rate against plan ids
        $draftRatesSheet = HealthRateControl::whereIn('health_plan_id', $planIds)
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->first();

        // If not found, add new draft rate sheet
        if (! $draftRatesSheet) {
            return $this->addRate($data, $planIds);
        }

        // Else add rate to existing draft rate sheet
    }

    private function addRate(array $data, array $planIds): HealthRate
    {
        $data['version'] = $this->deriveVersion($data['health_plan_id'], $planIds);
        $plan = $this->healthPlanService->getPlanById($data['health_plan_id']);

        $healthRate = DB::transaction(function () use ($data, $plan) {
            // Add health rates control (rate sheet)
            $healthRateControl = HealthRateControl::create([
                'health_plan_id' => $data['health_plan_id'],
                'version' => $data['version'],
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'],
                'total_records' => 1,
                'created_by' => $data['user_id'],
            ]);

            // Add health rate
            $healthRate = HealthRate::create([
                'health_plan_id' => $data['health_plan_id'],
                'health_rate_control_id' => $healthRateControl->id,
                'version' => $data['version'],
                'health_plan_co_payment_id' => $data['health_plan_co_payment_id'],
                'text' => $data['text'],
                'text_ar' => $data['text_ar'],
                'premium' => $data['premium'],
                'is_active' => $data['is_active'],
                'emirate_type' => $data['emirate_type'],
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'gender' => $plan->gender_enabled ? $data['gender'] : null,
                'cohort' => $plan->cohort_enabled ? $data['cohort'] : null,
                'marital_status' => $plan->marital_status_enabled
                    && strtolower($data['gender']) == strtolower(GenderEnum::FEMALE->value) ? $data['marital_status'] : null,
            ]);

            return $healthRate;
        });

        return $healthRate;
    }

    private function deriveVersion(int $planId, array $planIds): float
    {
        // Get active rate sheet version
        $activeRateSheet = HealthRateControl::where('health_plan_id', $planId)
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value)
            ->first();

        // If found, return next version
        if ($activeRateSheet) {
            return $activeRateSheet->version + 0.1;
        }

        return 1.0;
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
