<?php

namespace App\Http\Controllers;

use App\Services\ApplicationStorageService;
use App\Services\CarLeadAllocationDashboardService;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CarLeadAllocationController extends Controller
{
    protected $leadAllocationService;
    protected $applicationStorageService;

    public function __construct(CarLeadAllocationDashboardService $leadAllocationService, ApplicationStorageService $applicationStorageService)
    {
        $this->leadAllocationService = $leadAllocationService;
        $this->applicationStorageService = $applicationStorageService;
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
            $isAutoAllocationWorking = $this->applicationStorageService->getValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH');
            $isRenewalLogicWorking = $this->applicationStorageService->getValueByKey('CAR_RENEWAL_LOGIC_SWITCH');
            $data = $this->leadAllocationService->getGridData();
            foreach ($data as $key => $value) {
                $totalAssignedLeadCount += $value->allocationCount;
                if ($value->is_available == 1) {
                    $availableUsers++;
                } else {
                    $unAvailableUsers++;
                }
            }
            if ($request->ajax()) {
                return Datatables::of($data)
                    ->addIndexColumn()
                    ->make(true);
            }

            return view('user.car-lead-allocation', compact(['totalAssignedLeadCount', 'availableUsers', 'unAvailableUsers', 'isAutoAllocationWorking']));
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
