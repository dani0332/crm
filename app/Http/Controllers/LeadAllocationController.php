<?php

namespace App\Http\Controllers;

use App\Models\LeadAllocation;
use App\Services\LeadAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Auth;
use DataTables;

class LeadAllocationController extends Controller
{

    protected $leadAllocationService;
    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (Gate::allows('view-lead-allocation', Auth::user())) {
            $totalAssignedLeadCount = 0;
            $availableUsers = 0;
            $unAvailableUsers = 0;
            $data = $this->leadAllocationService->getGridData($request);
            foreach ($data as $key => $value) {
                $totalAssignedLeadCount += $value->allocation_count;
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
            return view('user.lead-allocation', compact(['totalAssignedLeadCount', 'availableUsers', 'unAvailableUsers']));
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

    public function updateAvailability(Request $request)
    {
        $leadAllocationUser = LeadAllocation::where('user_id', $request->aid)->where('id', $request->id)->first();

        if (isset($request->is_available)) $leadAllocationUser->is_available = $request->is_available;
        if (isset($request->max_cap)) $leadAllocationUser->max_capacity = $request->max_cap;
        $leadAllocationUser->save();
    }
}
