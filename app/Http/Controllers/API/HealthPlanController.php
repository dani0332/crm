<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateHealthPlanRequest;
use App\Http\Requests\Api\DeleteHealthPlanRequest;
use App\Http\Requests\Api\GetStatusVersionHealthPlanRequest;
use App\Http\Requests\Api\PublishHealthPlanRequest;
use App\Http\Requests\Api\UpdateHealthPlanRequest;
use App\Http\Resources\HealthPlanResource;
use App\Services\HealthPlanService;
use Illuminate\Http\JsonResponse;

class HealthPlanController extends Controller
{
    public function __construct(private HealthPlanService $healthPlanService) {}

    public function create(CreateHealthPlanRequest $request): JsonResponse
    {
        $plan = $this->healthPlanService->create($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => new HealthPlanResource($plan),
        ]);
    }

    public function update(UpdateHealthPlanRequest $request): JsonResponse
    {
        $plan = $this->healthPlanService->update($request->id, $request->validated());

        return response()->json([
            'status' => 'success',
            'data' => new HealthPlanResource($plan),
        ]);
    }

    public function getStatusVersions(GetStatusVersionHealthPlanRequest $request): JsonResponse
    {
        $plans = $this->healthPlanService->getStatusVersions($request->validated('parent_id'), $request->validated('status'));

        return response()->json([
            'status' => 'success',
            'data' => HealthPlanResource::collection($plans),
        ]);
    }

    public function delete(DeleteHealthPlanRequest $request): JsonResponse
    {
        $this->healthPlanService->delete($request->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Health plan deleted successfully',
        ]);
    }

    public function publish(PublishHealthPlanRequest $request): JsonResponse
    {
        $this->healthPlanService->publish($request->id, $request->user_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Health plan scheduled successfully. It will be activated on the rate sheet effective from date.',
        ]);
    }
}
