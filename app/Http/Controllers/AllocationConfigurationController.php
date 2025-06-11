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
     * Display a listing of allocation configurations
     */
    public function index(Request $request): Response
    {
        $query = AllocationConfiguration::query();

        // Apply filters
        if ($request->filled('quote_type')) {
            $query->where('quote_type', $request->quote_type);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('quote_type', 'like', '%' . $request->search . '%')
                  ->orWhere('quote_type_id', 'like', '%' . $request->search . '%');
            });
        }

        $configurations = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return inertia('Admin/AllocationConfig/AllocationConfiguration/Index', [
            'configurations' => $configurations,
            'filters' => $request->only(['quote_type', 'search']),
            'quoteTypes' => QuoteTypes::allTypesWithIds(),
        ]);
    }

    /**
     * Show the form for creating a new allocation configuration
     */
    public function create(): Response
    {
        return inertia('Admin/AllocationConfig/AllocationConfiguration/Form', [
            'quoteTypes' => QuoteTypes::allTypesWithIds(),
            'advisors' => $this->allocationConfigurationService->getAdvisors(),
            'nationalities' => $this->allocationConfigurationService->getNationalities(),
        ]);
    }

    /**
     * Store a newly created allocation configuration
     */
    public function store(AllocationConfigurationRequest $request): RedirectResponse
    {
        $configuration = $this->allocationConfigurationService->createConfiguration(
            $request->validated(),
            Auth::id()
        );

        return redirect()->route('admin.allocation-configuration.show', $configuration->id)
            ->with('success', 'Allocation configuration created successfully.');
    }

    /**
     * Display the specified allocation configuration
     */
    public function show(AllocationConfiguration $allocationConfiguration): Response
    {
        return inertia('Admin/AllocationConfig/AllocationConfiguration/Show', [
            'configuration' => $allocationConfiguration,
            'quoteTypes' => QuoteTypes::allTypesWithIds(),
        ]);
    }

    /**
     * Show the form for editing the specified allocation configuration
     */
    public function edit(AllocationConfiguration $allocationConfiguration): Response
    {
        return inertia('Admin/AllocationConfig/AllocationConfiguration/Form', [
            'configuration' => $allocationConfiguration,
            'quoteTypes' => QuoteTypes::allTypesWithIds(),
            'advisors' => $this->allocationConfigurationService->getAdvisors(),
            'nationalities' => $this->allocationConfigurationService->getNationalities(),
        ]);
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

        return redirect()->route('admin.allocation-configuration.show', $allocationConfiguration->id)
            ->with('success', 'Allocation configuration updated successfully.');
    }

    /**
     * Remove the specified allocation configuration
     */
    public function destroy(AllocationConfiguration $allocationConfiguration): RedirectResponse
    {
        $this->allocationConfigurationService->deleteConfiguration($allocationConfiguration, Auth::id());

        return redirect()->route('admin.allocation-configuration.index')
            ->with('success', 'Allocation configuration deleted successfully.');
    }

    /**
     * Get audit logs for the allocation configuration
     */
    public function getAuditLogs(AllocationConfiguration $allocationConfiguration)
    {
        $auditLogs = $allocationConfiguration->audits()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($auditLogs);
    }
}
