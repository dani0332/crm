<?php

namespace App\Http\Controllers;

use Inertia\Response;
use App\Enums\QuoteTypes;
use App\Models\QuoteType;
use Illuminate\Http\Request;
use App\Enums\PermissionsEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Services\AllocationConfigurationService;
use App\Models\Allocation\AllocationConfiguration;
use App\Http\Requests\AllocationConfigurationRequest;

class AllocationConfigurationController extends Controller
{
    public function __construct(
        private readonly AllocationConfigurationService $allocationConfigurationService
    ) {
        $this->middleware('permission:' . PermissionsEnum::ILA_CONFIG_ALL_LOB);
    }

    private function getQuoteTypes()
    {
        return QuoteType::where('is_active', 1)->whereIn('short_code', ['SAV'])->get();
    }

    public function index(Request $request): Response
    {
        return inertia('Admin/AllocationConfig/AllocationConfiguration/Form', [
            'quoteTypes' => $this->getQuoteTypes(),
            'nationalities' => $this->allocationConfigurationService->getNationalities(),
        ]);
    }

    public function fetchConfiguration(Request $request)
    {
        $request->validate([
            'quote_type' => [Rule::enum(QuoteTypes::class)],
        ]);

        $quoteType = QuoteTypes::from($request->quote_type);

        $configuration = $this->allocationConfigurationService->getConfig($quoteType);

        return response()->json([
            'success' => true,
            'data' => $configuration,
        ]);
    }

    public function store(AllocationConfigurationRequest $request)
    {
        try {
            $configuration = $this->allocationConfigurationService->createConfiguration(
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
