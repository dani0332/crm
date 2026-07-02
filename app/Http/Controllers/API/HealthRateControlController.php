<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeleteHealthRateSheetRequest;
use App\Services\HealthRateControlService;
use Illuminate\Http\JsonResponse;

class HealthRateControlController extends Controller
{
    public function __construct(private HealthRateControlService $healthRateControlService) {}

    public function delete(DeleteHealthRateSheetRequest $request): JsonResponse
    {
        $this->healthRateControlService->delete($request->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Rate sheet deleted successfully',
        ]);
    }
}
