<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLeadAllocationRequest;
use App\Models\LeadAllocation;
use App\Services\ApplicationStorageService;
use App\Services\CacheService;
use App\Services\TravelLeadAllocationDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TravelLeadAllocationController extends Controller
{
    protected $travelLeadAllocationService;
    protected $applicationStorageService;
    protected $cacheService;
    protected $teamService;
    protected $userService;
    protected $tierService;

    public function __construct(
        TravelLeadAllocationDashboardService $travelLeadAllocationService,
        ApplicationStorageService $applicationStorageService,
        CacheService $cacheService
    ) {
        $this->travelLeadAllocationService = $travelLeadAllocationService;
        $this->applicationStorageService = $applicationStorageService;
        $this->cacheService = $cacheService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (Gate::allows('view-lead-allocation', auth()->user())) {
            $data = $this->travelLeadAllocationService->getSicUsersGridData();

            return inertia('LeadAllocation/Travel', [
                'data' => $data,
            ]);
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function updateUserHardStopStatus(UpdateLeadAllocationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $leadAllocation = LeadAllocation::select('lead_allocation.*')
                ->whereHas('leadAllocationUser', function ($query) use ($validated) {
                    $query->where('id', $validated['userId']);
                })
                ->activeUser()
                ->travelQuote()
                ->first();

            if (! $leadAllocation) {
                Log::error('Lead allocation record not found for user', [
                    'user_id' => $validated['userId'],
                ]);

                return response()->json([
                    'message' => 'Lead allocation record not found for the specified user.',
                ], 404);
            }

            $leadAllocation->update(['is_hardstop' => $validated['status']]);

            Log::info('Successfully updated is_hardstop status', [
                'user_id' => $validated['userId'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'message' => 'Hard stop status updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update is_hardstop status', [
                'user_id' => $request->input('userId'),
                'status' => $request->input('status'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while updating hard stop status.',
            ], 500);
        }
    }
}
