<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateHealthPlanRequest;
use App\Services\HealthPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthPlanController extends Controller
{
    public function __construct(private HealthPlanService $healthPlanService) {}

    public function getList(Request $request): JsonResponse
    {
        $plans = $this->healthPlanService->getList($request);

        return response()->json($plans);
    }
    public function getPlan($id): JsonResponse
    {
        $plan = $this->healthPlanService->getPlanById($id);

        return response()->json($plan);
    }

    public function create(CreateHealthPlanRequest $request): JsonResponse
    {
        $plan = $this->healthPlanService->create($request->validated());

        return response()->json($plan);
    }
}
