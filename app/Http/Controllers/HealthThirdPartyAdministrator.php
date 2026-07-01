<?php

namespace App\Http\Controllers;

use App\Services\HealthThirdPartyAdministratorService;
use Illuminate\Http\JsonResponse;

class HealthThirdPartyAdministrator extends Controller
{
    public function __construct(
        private HealthThirdPartyAdministratorService $healthThirdPartyAdministratorService
    ) {}

    public function getByInsuranceProvider(int $insuranceProviderId): JsonResponse
    {
        $data = $this->healthThirdPartyAdministratorService->getByInsuranceProviderId($insuranceProviderId);

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }
}
