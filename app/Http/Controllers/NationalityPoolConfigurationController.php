<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\NationalityPoolConfigurationRequest;
use App\Models\CanonicalNationality;
use App\Models\NationalityPool;
use Illuminate\Http\JsonResponse;
use Inertia\Response;

class NationalityPoolConfigurationController extends Controller
{
    public function __construct()
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
        $nationalityPoolConfigurations = NationalityPool::all();

        return inertia('Admin/AllocationConfig/NationalityPool/Index', [
            'gbpNationalities' => $nationalities,
            'nationalityPoolConfigurations' => $nationalityPoolConfigurations,
        ]);
    }

    public function save(NationalityPoolConfigurationRequest $request): JsonResponse
    {
        try {
            $codes = collect($request->canonical_nationality_codes)->implode(',');

            NationalityPool::create([
                'effective_from' => $request->effective_from,
                'canonical_nationality_codes' => $codes,
            ]);

            return response()->json(['message' => 'Nationality pool configuration saved successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}
