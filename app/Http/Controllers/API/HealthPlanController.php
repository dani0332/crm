<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\HealthPlanService;

class HealthPlanController extends Controller
{
    public function __construct(private HealthPlanService $healthPlanService) {}
    public function getPlan($planId)
    {
        $plan = HealthPlan::find($planId);

        return response()->json($plan);
    }
}
