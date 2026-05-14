<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Enums\TeamTypeEnum;
use App\Http\Requests\AllocationConfigurationRequest;
use App\Http\Requests\FetchAllocationConfigurationRequest;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\BusinessTypeOfInsurance;
use App\Models\Department;
use App\Models\HealthPlanType;
use App\Models\SubArea;
use App\Models\Team;
use App\Services\AllocationConfiguration\AllocationConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class AllocationConfigurationController extends Controller
{
    public function __construct(
        private readonly AllocationConfigurationService $allocationConfigurationService
    ) {
        $this->middleware('permission:'.PermissionsEnum::ILA_CONFIG_ALL_LOB);
    }

    public function index(Request $request): Response
    {
        return inertia('Admin/AllocationConfig/AllocationConfiguration/Form', [
            'quoteTypes' => $this->allocationConfigurationService->getQuoteTypes(),
            'nationalities' => $this->allocationConfigurationService->getNationalities(),
        ]);
    }

    public function fetchConfiguration(FetchAllocationConfigurationRequest $request)
    {
        $configuration = $this->allocationConfigurationService->findConfig($request->getQuoteType());

        return response()->json([
            'success' => true,
            'data' => $configuration,
        ]);
    }

    public function store(AllocationConfigurationRequest $request)
    {
        try {
            $configuration = $this->allocationConfigurationService->createConfiguration(
                $request->getQuoteType(),
                $request->validated(),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Allocation configuration saved successfully.',
                'data' => $configuration,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save allocation configuration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(AllocationConfigurationRequest $request, AllocationConfiguration $allocationConfiguration)
    {
        try {
            $updatedConfiguration = $this->allocationConfigurationService->updateConfiguration(
                $allocationConfiguration,
                $request->getQuoteType(),
                $request->validated(),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Allocation configuration updated successfully.',
                'data' => $updatedConfiguration,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update allocation configuration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTeams()
    {
        try {
            $teams = Team::where('type', TeamTypeEnum::TEAM)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $teams,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teams.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPlanTypes()
    {
        try {
            $planTypes = HealthPlanType::orderBy('text')->get(['id', 'text']);

            return response()->json([
                'success' => true,
                'data' => $planTypes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plan types.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getBusinessTypes()
    {
        try {
            $businessTypes = BusinessTypeOfInsurance::orderBy('id')
                ->get(['id', 'text as name']);

            return response()->json([
                'success' => true,
                'data' => $businessTypes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch business types.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSubAreas()
    {
        try {
            $subAreas = SubArea::orderBy('text')
                ->get(['id', 'text as name']);

            return response()->json([
                'success' => true,
                'data' => $subAreas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sub areas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDepartments()
    {
        try {
            $departments = Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $departments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch departments.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
