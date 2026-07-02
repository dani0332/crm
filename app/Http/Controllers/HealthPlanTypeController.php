<?php

namespace App\Http\Controllers;

use App\Services\HealthPlanTypeService;
use Illuminate\Http\JsonResponse;

class HealthPlanTypeController extends Controller
{
    public function __construct(
        private HealthPlanTypeService $healthPlanTypeService
    ) {}

    public function getByEmirate(int $emirateId): JsonResponse
    {
        $data = $this->healthPlanTypeService->getByEmirateId($emirateId);

        return response()->json([
            'status' => true,
            'data' => $data]);
    }
}
