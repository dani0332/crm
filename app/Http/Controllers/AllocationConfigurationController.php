<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Http\Requests\AllocationConfigurationRequest;
use App\Models\Allocation\AllocationConfiguration;
use App\Services\AllocationConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class AllocationConfigurationController extends Controller
{
    public function __construct(
        private readonly AllocationConfigurationService $allocationConfigurationService
    ) {
    }

    /**
     * Display the allocation configuration form
     */
    public function index(Request $request): Response
    {
        return inertia('Admin/AllocationConfig/AllocationConfiguration/Form', [
            'quoteTypes' => QuoteTypes::allTypesWithIds(),
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
    public function store(AllocationConfigurationRequest $request): RedirectResponse
    {
        $this->allocationConfigurationService->createConfiguration(
            $request->validated(),
            Auth::id()
        );

        return redirect()->route('admin.allocation-configuration.index', ['quote_type' => $request->quote_type])
            ->with('success', 'Allocation configuration saved successfully.');
    }

    /**
     * Update the specified allocation configuration
     */
    public function update(AllocationConfigurationRequest $request, AllocationConfiguration $allocationConfiguration): RedirectResponse
    {
        $this->allocationConfigurationService->updateConfiguration(
            $allocationConfiguration,
            $request->validated(),
            Auth::id()
        );

        return redirect()->route('admin.allocation-configuration.index', ['quote_type' => $request->quote_type])
            ->with('success', 'Allocation configuration updated successfully.');
    }
}
