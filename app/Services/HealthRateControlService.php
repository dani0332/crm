<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRateControl;
use Illuminate\Support\Facades\DB;

class HealthRateControlService extends BaseService
{
    public function getByPlanIdAndStatus(int $planId, array $statuses): ?HealthRateControl
    {
        return HealthRateControl::where('health_plan_id', $planId)
            ->whereIn('status', $statuses)
            ->first();
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $rateSheet = HealthRateControl::findOrFail($id);
            $plan = $rateSheet->healthPlan;

            $rateSheet->rates()->delete();
            $rateSheet->delete();

            // If plan is scheduled, make it draft
            if ($plan->status == HealthPlanRateSheetStatusEnum::SCHEDULED->value) {
                $plan->status = HealthPlanRateSheetStatusEnum::DRAFT->value;
                $plan->save();
            }
        });
    }
}
