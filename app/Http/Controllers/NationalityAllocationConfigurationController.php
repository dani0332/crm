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
    public function __construct(protected NationalityAllocationService $nationalityAllocationService)
    {
        $this->middleware('permission:'.PermissionsEnum::NATIONALITY_ALLOCATION_CONFIG);
    }

    public function index(Request $request)
    {
        $configurations = NationalityAllocationConfiguration::with(['nationality', 'quoteType', 'users'])
            ->when($request->filled('quote_type_id'), function ($query) use ($request) {
                $query->where('quote_type_id', $request->quote_type_id);
            })
            ->when($request->filled('nationality_id'), function ($query) use ($request) {
                $query->where('nationality_id', $request->nationality_id);
            })
            ->when($request->filled('created_at'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->created_at);
            })
            ->when($request->filled('created_at_end'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->created_at_end);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->inactive();
                }
            })
            ->when($request->filled('should_skip_sic'), function ($query) use ($request) {
                $query->where('should_skip_sic', $request->should_skip_sic);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $nationalities = $this->getNationalities();
        $quoteTypes = $this->getQuoteTypes();

        return inertia('Admin/AllocationConfig/NationalityAllocation/Index', [
            'configurations' => $configurations,
            'nationalities' => $nationalities,
            'quoteTypes' => $quoteTypes,
            'filters' => $request->only(['quote_type_id', 'nationality_id', 'created_at', 'created_at_end', 'status', 'should_skip_sic']),
        ]);
    }

    public function create()
    {
        $nationalities = $this->getNationalities();
        $quoteTypes = $this->getQuoteTypes();
        $users = $this->getUsers();

        return inertia('Admin/AllocationConfig/NationalityAllocation/Form', [
            'nationalities' => $nationalities,
            'quoteTypes' => $quoteTypes,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'quote_type_id' => 'required|exists:quote_type,id',
            'nationality_id' => 'required|exists:nationality,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'should_skip_sic' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $existingConfig = NationalityAllocationConfiguration::where('quote_type_id', $validated['quote_type_id'])
            ->where('nationality_id', $validated['nationality_id'])
            ->first();

        if ($existingConfig) {
            return back()->withErrors([
                'quote_type_id' => 'A configuration with this quote type and nationality already exists.',
            ])->withInput();
        }

        // Set activated_at based on is_active flag
        $data = [
            'quote_type_id' => $validated['quote_type_id'],
            'nationality_id' => $validated['nationality_id'],
            'should_skip_sic' => $validated['should_skip_sic'] ?? false,
            'activated_at' => $validated['is_active'] ? now() : null,
            'user_ids' => $validated['user_ids'],
        ];

        $configuration = $this->nationalityAllocationService->createConfiguration($data, Auth::id());

        return redirect()->route('admin.nationality-allocation-config.show', $configuration->id)
            ->with('success', 'Nationality allocation configuration created successfully!');
    }

    public function show(NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        return inertia('Admin/AllocationConfig/NationalityAllocation/Show', [
            'configuration' => $nationalityAllocationConfig->load(['nationality', 'quoteType', 'users', 'createdBy', 'updatedBy']),
        ]);
    }

    public function edit(NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        $nationalities = $this->getNationalities();
        $quoteTypes = $this->getQuoteTypes();
        $users = $this->getUsers();

        return inertia('Admin/AllocationConfig/NationalityAllocation/Form', [
            'configuration' => $nationalityAllocationConfig->load(['nationality', 'quoteType', 'users']),
            'nationalities' => $nationalities,
            'quoteTypes' => $quoteTypes,
            'users' => $users,
        ]);
    }

    public function update(Request $request, NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        $validated = $request->validate([
            'quote_type_id' => 'required|exists:quote_type,id',
            'nationality_id' => 'required|exists:nationality,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'should_skip_sic' => 'boolean',
            'is_active' => 'boolean',
        ]);

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
            'should_skip_sic' => $validated['should_skip_sic'] ?? false,
            'updated_by' => Auth::id(),
            'activated_at' => $validated['is_active'] ? ($nationalityAllocationConfig->activated_at ?? now()) : null,
        ]);

        $nationalityAllocationConfig->users()->sync($validated['user_ids']);

        return redirect()->route('admin.nationality-allocation-config.show', $nationalityAllocationConfig->id)
            ->with('success', 'Nationality allocation configuration updated successfully!');
    }

    public function destroy(NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        $this->nationalityAllocationService->deleteConfiguration($nationalityAllocationConfig->id);

        return redirect()->route('admin.nationality-allocation-config.index')
            ->with('success', 'Nationality allocation configuration deleted successfully!');
    }

    public function getAuditLogs(Request $request, NationalityAllocationConfiguration $nationalityAllocationConfig)
    {
        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $logs = $this->nationalityAllocationService->getAuditLogs($nationalityAllocationConfig->id, $perPage);

        return response()->json($logs);
    }

    private function getNationalities()
    {
        return Nationality::where('is_active', 1)->get();
    }

    private function getQuoteTypes()
    {
        return QuoteType::where('is_active', 1)->whereNotIn('short_code', ['CORPLINE', 'GM', 'JBL', 'SAV-', 'BTC', 'COM', 'JOB'])->get();
    }

    private function getUsers()
    {
        return User::where('is_active', 1)->get();
    }
}
