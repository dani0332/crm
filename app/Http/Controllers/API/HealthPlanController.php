<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateHealthPlanRequest;
use App\Http\Requests\Api\DeleteHealthPlanRequest;
use App\Http\Requests\Api\GetStatusVersionHealthPlanRequest;
use App\Http\Requests\Api\HealthPlanDetailRequest;
use App\Http\Requests\Api\PublishHealthPlanRequest;
use App\Http\Requests\Api\UpdateHealthPlanRequest;
use App\Http\Resources\HealthPlanResource;
use App\Services\HealthPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HealthPlanController extends Controller
{
    public function __construct(private HealthPlanService $healthPlanService) {}

    public function getList(Request $request): AnonymousResourceCollection
    {
        $plans = $this->healthPlanService->getList($request);

        // Have to return like this due to pagination
        return HealthPlanResource::collection($plans)
            ->additional([
                'status' => 'success',
            ]);
    }
    public function getPlan(HealthPlanDetailRequest $request): JsonResponse
    {
        $plan = $this->healthPlanService->getPlanById($request->id);

        return response()->json([
            'status' => 'success',
            'data' => new HealthPlanResource($plan),
        ]);
    }

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
        $plans = $this->healthPlanService->getStatusVersions($request->parentId, $request->status);

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
        $this->healthPlanService->publish($request->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Health plan scheduled successfully. It will be activated on the rate sheet effective from date.',
        ]);
    }
}
