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
        $quoteType = $request->get('quote_type');
        $configuration = null;

        // If quote type is provided, try to find existing configuration
        if ($quoteType) {
            $configuration = AllocationConfiguration::where('quote_type', $quoteType)->first();
        }

        return inertia('Admin/AllocationConfig/AllocationConfiguration/Form', [
            'configuration' => $configuration,
            'quoteTypes' => QuoteTypes::allTypesWithIds(),
            'advisors' => $this->allocationConfigurationService->getAdvisors(),
            'nationalities' => $this->allocationConfigurationService->getNationalities(),
            'selectedQuoteType' => $quoteType,
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
