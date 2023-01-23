<?php

namespace App\Http\Controllers;

use App\Services\ApplicationStorageService;
use App\Services\CacheService;
use App\Services\CarLeadAllocationDashboardService;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CarLeadAllocationController extends Controller
{
    protected $carLeadAllocationService;
    protected $applicationStorageService;
    protected $cacheService;
    protected $teamService;
    protected $userService;
    protected $tierService;
    public function __construct(CarLeadAllocationDashboardService $carLeadAllocationService, ApplicationStorageService $applicationStorageService,
     CacheService $cacheService)
    {
        $this->carLeadAllocationService = $carLeadAllocationService;
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
            $totalAssignedLeadCount = 0;
            $availableUsers = 0;
            $unAvailableUsers = 0;
            $todayTotalLeadCount = $this->carLeadAllocationService->getTodaysCarTotalLeadsCount();
            $todayTotalUnAssignedLeadCount = $this->carLeadAllocationService->getTodaysCarTotalUnAssignedLeadsCount();
            $isAutoAllocationWorking = $this->applicationStorageService->getValueByKey('CAR_LEAD_ALLOCATION_MASTER_SWITCH');
            $isRenewalLeadAllocationWorking = $this->applicationStorageService->getValueByKey('CAR_RENEWAL_LEAD_ALLOCATION');
            $isFIFO = $this->applicationStorageService->getValueByKey('CAR_LEAD_PICKUP_FIFO');
            $data = $this->carLeadAllocationService->getGridData();
            foreach ($data as $key => $value) {
                $totalAssignedLeadCount = $totalAssignedLeadCount + ($value->auto_assignment_count + $value->manual_assignment_count);
                $value->isAvailable == 1 ? $availableUsers++ : $unAvailableUsers++;
            }
            if ($request->ajax()) {
                return Datatables::of($data)
                    ->addIndexColumn()
                    ->make(true);
            }

            return view('user.car-lead-allocation', compact(['totalAssignedLeadCount', 'availableUsers', 'unAvailableUsers', 'isAutoAllocationWorking', 'isRenewalLeadAllocationWorking', 'isFIFO', 'todayTotalLeadCount', 'todayTotalUnAssignedLeadCount']));
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function renderAdvisorConversionReport()
    {
        return view('dashboard.advisor-conversion-report');
    }
}
