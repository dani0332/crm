<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRateControl;
use Illuminate\Support\Facades\DB;

class HealthRateControlService extends BaseService
{
    public function getByPlanIdAndStatus(int $planId, HealthPlanRateSheetStatusEnum $status): ?HealthRateControl
    {
        return HealthRateControl::where('health_plan_id', $planId)
            ->where('status', $status)
            ->first();
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $rateSheet = HealthRateControl::find($id);
            $rateSheet->rates()->delete();
            $rateSheet->delete();
        });
    }
}
