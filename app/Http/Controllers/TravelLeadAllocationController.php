<?php

namespace App\Http\Controllers;

use App\Enums\LeadAllocationUserBLStatusFiltersEnum;
use App\Enums\QuoteTypes;
use App\Http\Requests\UpdateLeadAllocationRequest;
use App\Models\LeadAllocation;
use App\Services\TravelLeadAllocationDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TravelLeadAllocationController extends Controller
{
    public function __construct(
        private TravelLeadAllocationDashboardService $travelLeadAllocationService,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (Gate::allows('view-lead-allocation', auth()->user())) {
            $availableUsers = 0;
            $unAvailableUsers = 0;
            $totalAssignedLeadCount = 0;
            $todayTotalUnAssignedLeadCount = $this->travelLeadAllocationService->getTodaysTotalUnAssignedLeadsCount();
            $data = $this->travelLeadAllocationService->getSicUsersGridData($request->userBlStatus);

            foreach ($data as $row) {
                $totalAssignedLeadCount = $totalAssignedLeadCount + $row->allocationCount;
                $row->isAvailable ? $availableUsers++ : $unAvailableUsers++;
            }

            return inertia('LeadAllocation/Travel', [
                'userBLStatuses' => LeadAllocationUserBLStatusFiltersEnum::withLabels(),
                'quoteType' => QuoteTypes::TRAVEL->value,
                'availableUsers' => $availableUsers,
                'unAvailableUsers' => $unAvailableUsers,
                'totalAssignedLeadCount' => $totalAssignedLeadCount,
                'todayTotalUnAssignedLeadCount' => $todayTotalUnAssignedLeadCount,
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

            $leadAllocation = LeadAllocation::select(['id'])
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
