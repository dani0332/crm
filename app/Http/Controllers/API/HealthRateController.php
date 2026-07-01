<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateHealthRateRequest;
use App\Http\Requests\Api\DeleteHealthRateRequest;
use App\Http\Requests\Api\PublishHealthRateControlRequest;
use App\Http\Requests\Api\UpdateHealthRateRequest;
use App\Http\Resources\HealthRateResource;
use App\Services\HealthRateService;
use Illuminate\Http\JsonResponse;

class HealthRateController extends Controller
{
    public function __construct(
        private HealthRateService $healthRateService,
    ) {}

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
        $this->healthRateService->publishRateControl($request->id, $request->user_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Rate sheet scheduled successfully. It will be activated on the effective from date.',
        ]);
    }
}
