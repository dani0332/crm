<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Models\Nationality;
use App\Models\NationalityAllocationConfiguration;
use App\Models\QuoteType;
use App\Models\User;
use App\Services\NationalityAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NationalityAllocationConfigurationController extends Controller
{
    protected $nationalityAllocationService;

    public function __construct(NationalityAllocationService $nationalityAllocationService)
    {
        $this->middleware('permission:'.PermissionsEnum::SIC_HEALTH_CONFIG);
        $this->nationalityAllocationService = $nationalityAllocationService;
    }

    /**
     * Display a paginated listing of the nationality allocation configurations.
     */
    public function index(Request $request)
    {
        $query = NationalityAllocationConfiguration::with(['nationality', 'quoteType', 'users']);

        // Add filtering options
        if ($request->filled('quote_type_id')) {
            $query->where('quote_type_id', $request->quote_type_id);
        }

        if ($request->filled('nationality_id')) {
            $query->where('nationality_id', $request->nationality_id);
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', '>=', $request->created_at);
        }

        if ($request->filled('created_at_end')) {
            $query->whereDate('created_at', '<=', $request->created_at_end);
        }

        // Paginate the results
        $configurations = $query->paginate(10)->withQueryString();

        // Get lookup data
        $nationalities = Nationality::where('is_active', 1)->get();
        $quoteTypes = QuoteType::where('is_active', 1)->get();

        return inertia('Admin/AllocationConfig/NationalityAllocation/Index', [
            'configurations' => $configurations,
            'nationalities' => $nationalities,
            'quoteTypes' => $quoteTypes,
            'filters' => $request->only(['quote_type_id', 'nationality_id', 'created_at', 'created_at_end']),
        ]);
    }

    /**
     * Show the form for creating a new nationality allocation configuration.
     */
    public function create()
    {
        $nationalities = Nationality::where('is_active', 1)->get();
        $quoteTypes = QuoteType::where('is_active', 1)->get();
        $users = User::where('is_active', 1)->get();

        return inertia('Admin/AllocationConfig/NationalityAllocation/Form', [
            'nationalities' => $nationalities,
            'quoteTypes' => $quoteTypes,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created nationality allocation configuration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'quote_type_id' => 'required|exists:quote_type,id',
            'nationality_id' => 'required|exists:nationality,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Check if a configuration with the same quote type and nationality already exists
        $existingConfig = NationalityAllocationConfiguration::where('quote_type_id', $validated['quote_type_id'])
            ->where('nationality_id', $validated['nationality_id'])
            ->first();

        if ($existingConfig) {
            return back()->withErrors([
                'quote_type_id' => 'A configuration with this quote type and nationality already exists.',
            ])->withInput();
        }

        $this->nationalityAllocationService->createConfiguration($validated, Auth::id());

        return redirect()->route('admin.nationality-allocation-config.index')
            ->with('success', 'Nationality allocation configuration created successfully!');
    }

    /**
     * Display the specified nationality allocation configuration.
     */
    public function show(NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        $nationalityAllocationConfig->load(['nationality', 'quoteType', 'users', 'createdBy', 'updatedBy']);

        return inertia('Admin/AllocationConfig/NationalityAllocation/Show', [
            'configuration' => $nationalityAllocationConfig,
        ]);
    }

    /**
     * Show the form for editing the specified nationality allocation configuration.
     */
    public function edit(NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        $nationalityAllocationConfig->load(['nationality', 'quoteType', 'users']);

        $nationalities = Nationality::where('is_active', 1)->get();
        $quoteTypes = QuoteType::where('is_active', 1)->get();
        $users = User::where('is_active', 1)->get();

        return inertia('Admin/AllocationConfig/NationalityAllocation/Form', [
            'configuration' => $nationalityAllocationConfig,
            'nationalities' => $nationalities,
            'quoteTypes' => $quoteTypes,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified nationality allocation configuration.
     */
    public function update(Request $request, NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        $validated = $request->validate([
            'quote_type_id' => 'required|exists:quote_type,id',
            'nationality_id' => 'required|exists:nationality,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Check if another configuration with the same quote type and nationality already exists
        $existingConfig = NationalityAllocationConfiguration::where('quote_type_id', $validated['quote_type_id'])
            ->where('nationality_id', $validated['nationality_id'])
            ->where('id', '!=', $nationalityAllocationConfig->id)
            ->first();

        if ($existingConfig) {
            return back()->withErrors([
                'quote_type_id' => 'Another configuration with this quote type and nationality already exists.',
            ])->withInput();
        }

        $nationalityAllocationConfig->update([
            'quote_type_id' => $validated['quote_type_id'],
            'nationality_id' => $validated['nationality_id'],
            'updated_by' => Auth::id(),
        ]);

        $nationalityAllocationConfig->users()->sync($validated['user_ids']);

        return redirect()->route('admin.nationality-allocation-config.index')
            ->with('success', 'Nationality allocation configuration updated successfully!');
    }

    /**
     * Remove the specified nationality allocation configuration.
     */
    public function destroy(NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        $this->nationalityAllocationService->deleteConfiguration($nationalityAllocationConfig->id);

        return redirect()->route('admin.nationality-allocation-config.index')
            ->with('success', 'Nationality allocation configuration deleted successfully!');
    }
}
