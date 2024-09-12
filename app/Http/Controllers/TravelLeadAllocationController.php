<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Models\LeadAllocation;
use App\Services\ApplicationStorageService;
use App\Services\CacheService;
use App\Services\TravelLeadAllocationDashboardService;
use DataTables;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

    public function updateUserHardStopStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'userId' => 'required|integer|exists:users,id',
                'status' => 'required|boolean',
            ]);

            $leadAllocation = LeadAllocation::where('user_id', $validated['userId'])
                ->where('quote_type_id', QuoteTypes::TRAVEL->id())
                ->firstOrFail();

            $leadAllocation->is_hardstop = $validated['status'];
            $leadAllocation->save();

            Log::info('Successfully updated is_hardstop status', [
                'user_id' => $validated['userId'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'message' => 'Hard stop status updated successfully.',
            ], 200);
        } catch (ValidationException $e) {
            Log::warning('Validation failed for updating is_hardstop status', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'message' => 'Invalid input data.',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            Log::error('Lead allocation not found for user', [
                'user_id' => $request->userId,
            ]);

            return response()->json([
                'message' => 'Lead allocation not found for the specified user.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update is_hardstop status', [
                'user_id' => $request->userId,
                'status' => $request->status,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while updating hard stop status.',
            ], 500);
        }
    }
}
