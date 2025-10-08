<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\AllocationConfigurationRequest;
use App\Http\Requests\FetchAllocationConfigurationRequest;
use App\Models\Allocation\AllocationConfiguration;
use App\Services\AllocationConfigurationService;
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
            'quoteTypeCodeEnum' => [
                'SAVINGS' => 'Savings',
                'HOME' => 'Home',
                'LIFE' => 'Life',
                'PET' => 'Pet',
                'YACHT' => 'Yacht',
                'CYCLE' => 'Cycle',
            ],
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
}
