<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AllocationConfigurationRequest;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\QuoteType;
use App\Services\AllocationConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class AllocationConfigurationController extends Controller
{
    public function __construct(
        private readonly AllocationConfigurationService $allocationConfigurationService
    ) {}

    private function getQuoteTypes()
    {
        return QuoteType::where('is_active', 1)->whereIn('short_code', ['SAV'])->get();
    }

    /**
     * Display the allocation configuration form
     */
    public function index(Request $request): Response
    {
        return inertia('Admin/AllocationConfig/AllocationConfiguration/Form', [
            'quoteTypes' => $this->getQuoteTypes(),
            'nationalities' => $this->allocationConfigurationService->getNationalities(),
        ]);
    }

    /**
     * Fetch configuration for a specific quote type (API endpoint)
     */
    public function fetchConfiguration(Request $request)
    {
        $request->validate([
            'quote_type' => 'required|string',
        ]);

        $configuration = AllocationConfiguration::where('quote_type', $request->quote_type)->first();

        return response()->json([
            'success' => true,
            'data' => $configuration,
        ]);
    }

    /**
     * Store a newly created allocation configuration
     */
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

    /**
     * Update the specified allocation configuration
     */
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
