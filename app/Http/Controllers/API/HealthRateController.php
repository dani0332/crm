<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateHealthRateRequest;
use App\Http\Requests\Api\DeleteHealthRateRequest;
use App\Http\Requests\Api\HealthPlanDetailRequest;
use App\Http\Requests\Api\HealthRateDetailRequest;
use App\Http\Requests\Api\PublishHealthRateControlRequest;
use App\Http\Requests\Api\UpdateHealthRateRequest;
use App\Http\Resources\HealthRateResource;
use App\Services\HealthRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HealthRateController extends Controller
{
    public function __construct(
        private HealthRateService $healthRateService,
    ) {}

    public function getList(HealthPlanDetailRequest $request): AnonymousResourceCollection
    {
        $rates = $this->healthRateService->getList($request);

        return HealthRateResource::collection($rates)
            ->additional([
                'status' => 'success',
            ]);
    }

    public function getRate(HealthRateDetailRequest $request): JsonResponse
    {
        $rate = $this->healthRateService->getRateById($request->id);

        return response()->json([
            'status' => 'success',
            'data' => new HealthRateResource($rate),
        ]);
    }

    public function create(CreateHealthRateRequest $request): JsonResponse
    {
        $rate = $this->healthRateService->create($request->validated());

        return response()->json([
            'status' => 'success',
            'data' => new HealthRateResource($rate),
        ]);
    }

    public function update(UpdateHealthRateRequest $request): JsonResponse
    {
        $rate = $this->healthRateService->update($request->id, $request->validated());

        return response()->json([
            'status' => 'success',
            'data' => new HealthRateResource($rate),
        ]);
    }

    public function delete(DeleteHealthRateRequest $request): JsonResponse
    {
        $this->healthRateService->delete($request->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Health rate deleted successfully',
        ]);
    }

    public function publish(PublishHealthRateControlRequest $request): JsonResponse
    {
        $this->healthRateService->publishRateControl($request->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Rate control scheduled successfully. It will be activated on the effective from date.',
        ]);
    }
}
