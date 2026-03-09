<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\NationalityPoolConfigurationRequest;
use App\Http\Resources\NationalityPoolAuditResource;
use App\Models\CanonicalNationality;
use App\Services\NationalityPoolConfigurationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Response;

class NationalityPoolConfigurationController extends Controller
{
    public function __construct(private NationalityPoolConfigurationService $nationalityPoolConfigurationService)
    {
        $this->middleware('permission:'.PermissionsEnum::NATIONALITY_POOL_CONFIG);
    }

    public function index(): Response
    {
        // Fetch all nationalities
        $nationalities = CanonicalNationality::where('nationality_synonym', 0)
            ->select('canonical_nationality_code as value', 'canonical_nationality_name as label')
            ->orderBy('canonical_nationality_name')->get();

        // Fetch nationality pool configurations
        $nationalityPoolConfigurations = $this->nationalityPoolConfigurationService->getData();

        return inertia('Admin/AllocationConfig/NationalityPool/Index', [
            'gbpNationalities' => $nationalities,
            'nationalityPoolConfigurations' => $nationalityPoolConfigurations,
        ]);
    }
    public function getAuditLogs(string $type): JsonResponse
    {
        try {
            $auditLogs = $this->nationalityPoolConfigurationService->getAuditLogs($type);
            $auditLogs = NationalityPoolAuditResource::collection($auditLogs);

            return response()->json(['data' => $auditLogs]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function save(NationalityPoolConfigurationRequest $request): JsonResponse
    {
        try {
            $this->nationalityPoolConfigurationService->saveData($request->all());

            return response()->json(['message' => 'Nationality pool configuration saved successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
