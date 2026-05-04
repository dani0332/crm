<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HealthPlanDetailRequest;
use App\Http\Requests\Api\HealthRateDetailRequest;
use App\Http\Resources\HealthRateResource;
use App\Services\HealthRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HealthRateController extends Controller
{
    public function __construct(private HealthRateService $healthRateService) {}

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
}
