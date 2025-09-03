<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLeadAllocationRequest;
use App\Models\LeadAllocation;
use App\Services\Logger\LoggerService;
use App\Services\TravelLeadAllocationDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
    public function index()
    {
        if (Gate::allows('view-lead-allocation', auth()->user())) {
            $availableUsers = 0;
            $unAvailableUsers = 0;
            $totalAssignedLeadCount = 0;
            $todayTotalUnAssignedLeadCount = $this->travelLeadAllocationService->getTodaysTotalUnAssignedLeadsCount();
            $data = $this->travelLeadAllocationService->getSicUsersGridData();

            foreach ($data as $row) {
                $totalAssignedLeadCount = $totalAssignedLeadCount + $row->allocationCount;
                $row->isAvailable ? $availableUsers++ : $unAvailableUsers++;
            }

            return inertia('LeadAllocation/Travel', [
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
                LoggerService::error('Lead allocation record not found for user', [
                    'user_id' => $validated['userId'],
                ]);

                return response()->json([
                    'message' => 'Lead allocation record not found for the specified user.',
                ], 404);
            }

            $leadAllocation->update(['is_hardstop' => $validated['status']]);

            LoggerService::info('Successfully updated is_hardstop status', [
                'user_id' => $validated['userId'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'message' => 'Hard stop status updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            LoggerService::error('Failed to update is_hardstop status', [
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
