<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthRateControl;

class HealthRateControlService extends BaseService
{
    public function getByPlanIdAndStatus(int $planId, HealthPlanRateSheetStatusEnum $status): ?HealthRateControl
    {
        return HealthRateControl::where('health_plan_id', $planId)
            ->where('status', $status)
            ->first();
    }
}
