<?php

namespace App\Http\Controllers\Allocations;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Enums\RolesEnum;
use App\Services\Logger\LoggerService;
use App\Enums\LeadSourceEnum;
use App\Models\ClaimRequest;
use App\Http\Requests\ClaimAvailabilityRequest;
use App\Services\ClaimAllocation\ClaimAllocationService;

class ClaimAllocationController extends Controller
{
   

    

    public function index()
    {
        $totalAssignedLeadCount = 0;
        $availableUsers = 0;
        $unAvailableUsers = 0;

        $todayTotalLeadCount = $this->getTodaysTotalLeadsCount();
        $todayTotalUnAssignedLeadCount = $this->getTodaysTotalUnAssignedLeadsCount();

        $data = $this->getClaimManagers();
        foreach ($data as $value) {
            $totalAssignedLeadCount = $totalAssignedLeadCount + $value->allocationCount;
            $value->isAvailable == 1 ? $availableUsers++ : $unAvailableUsers++;
        }

        $data = [
            'totalAssignedLeadCount' => $totalAssignedLeadCount,
            'availableUsers' => $availableUsers,
            'unAvailableUsers' => $unAvailableUsers,
            'todayTotalLeadCount' => $todayTotalLeadCount,
            'todayTotalUnAssignedLeadCount' => $todayTotalUnAssignedLeadCount,
            'data' => $data,
            'lobSpecificLeadAllocation' => null,
        ];

   
        
        return inertia('ClaimAllocation/Index', $data);
    }
    private function getClaimManagers()
    {
        try {
            $users = User::activeUser()
                ->select(
                    'users.id as userId',
                    'users.name as userName',
                    DB::RAW('(la.manual_assignment_count  + la.auto_assignment_count) as allocationCount'),
                    DB::RAW("DATE_FORMAT(FROM_UNIXTIME(la.last_allocated), '%d-%m-%Y %H:%i:%s') as lastAllocation"),
                    'la.max_capacity as maxCapacity',
                    'users.status as isAvailable',
                    'la.id as id',
                    'la.manual_assignment_count as manualAllocationCount',
                    'la.auto_assignment_count as autoAllocationCount',
                    'la.reset_cap',
                    'qt.code as quoteTypeCode',
                )
                ->join('claims_lead_allocation_config as la', 'la.user_id', 'users.id')
                ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->join('quote_type as qt', 'qt.id', '=', 'la.quote_type_id')
                ->whereIn('r.name', [RolesEnum::ClaimsManager])
                ->groupBy('users.name', 'users.id', 'la.id');
          

            return $users->get();
        } catch (\Exception $e) {
            LoggerService::error("claim allocation get advisors error: " . $e->getMessage());
            return [];
        }
    }


    private function getQuotesBaseQuery()
    {
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        return ClaimRequest::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD]);
    }

    private function getTodaysTotalLeadsCount()
    {
        return $this->getQuotesBaseQuery()->count();
    }

    private function getTodaysTotalUnAssignedLeadsCount()
    {
        return $this->getQuotesBaseQuery()
            ->whereNull('manager_id')
            ->count();
    }

    public function updateAvailability(ClaimAvailabilityRequest $request)
    {
        if ( empty($request->items)) {
            return response()->json([
                'message' => 'Items not found.',
            ], 404);
        }
        app(ClaimAllocationService::class)->updateAvailability($request->items);
        return response()->json([
            'message' =>'Claim manager status updated successfully.',
        ], 200);
    }

    public function updateCaps(ClaimAvailabilityRequest $request)
    {

        if (isset($request->items)) {
               
        
                app(ClaimAllocationService::class)->updateCaps($request->items);
                return response()->json([
                    'message' => 'Max Capacity Updated Successfully.',
                ], 200);
            } else {
                return back()->with('info', 'Please select at least one item.');
            }
    }

    public function updateResetCapSwitch(ClaimAvailabilityRequest $request)
    {
        $requester = auth()->user();
        if (isset($request->items)) {
            LoggerService::info(self::class."::updateResetCapSwitch - Requester: {$requester->id}: {$requester->name} ({$requester->email})".json_encode($request->all()));
        
            app(ClaimAllocationService::class)->resetCap($request->items);
            return response()->json([
                'message' => 'Reset Cap Capacity Updated Successfully.',
            ], 200);
        } else {
            return back()->with('info', 'Please select at least one item.');
            LoggerService::error(self::class."::updateResetCapSwitch - Requester: {$requester->id}: {$requester->name} ({$requester->email})".json_encode($request->all()));
        }
    }


}
