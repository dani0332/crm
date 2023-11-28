<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Enums\TeamTypeEnum;
use App\Enums\UserStatusEnum;
use App\Events\UserStatusChanged;
use App\Jobs\ReAssignCarLeadsJob;
use App\Jobs\ReAssignHealthLeadsJob;
use App\Models\LeadAllocation;
use App\Models\Team;
use App\Models\User;
use App\Services\ApplicationStorageService;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use App\Services\LeadAllocationService;
use App\Traits\TeamHierarchyTrait;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LeadAllocationController extends Controller
{
    use TeamHierarchyTrait;

    protected $leadAllocationService;
    protected $applicationStorageService;

    public function __construct(LeadAllocationService $leadAllocationService, ApplicationStorageService $applicationStorageService)
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
            $isAutoAllocationWorking = $this->applicationStorageService->getValueByKey('LEAD_ALLOCATION_JOB_SWITCH');
            $data = $this->leadAllocationService->getGridData();
            foreach ($data as $key => $value) {
                $totalAssignedLeadCount += $value->allocation_count;
                if ($value->is_available == 1) {
                    $availableUsers++;
                } else {
                    $unAvailableUsers++;
                }
            }
            // if ($request->ajax()) {
            //     return Datatables::of($data)
            //         ->addIndexColumn()
            //         ->make(true);
            // }

            return inertia('LeadAllocation/Health', [
                'totalAssignedLeadCount' => $totalAssignedLeadCount,
                'availableUsers' => $availableUsers,
                'unAvailableUsers' => $unAvailableUsers,
                'isAutoAllocationWorking' => (int) $isAutoAllocationWorking,
                'data' => $data,
            ]);
            // return view('user.lead-allocation', compact(['totalAssignedLeadCount', 'availableUsers', 'unAvailableUsers', 'isAutoAllocationWorking']));
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
        $updateLogString = '----- Update done successfully to change the';

        $leadAllocationUser = LeadAllocation::where('user_id', $request->userId)->where('id', $request->id)->first();

        if (isset($request->reason)) {

            if ($request->reason != UserStatusEnum::OFFLINE && $request->reason != UserStatusEnum::ONLINE) {
                $car = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Car)->first();
                $health = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Health)->first();
                if ($this->userHaveProduct($request->userId, $car->id)) {
                    info('user belong to car so dispatching car reassignment job');
                    dispatch(new ReAssignCarLeadsJob(app(CarAllocationService::class), $request->userId));
                }
                if ($this->userHaveProduct($request->userId, $health->id)) {
                    info('user belong to health so dispatching health reassignment job');
                    dispatch(new ReAssignHealthLeadsJob(app(HealthAllocationService::class), $request->userId));
                }
            }

            $user = User::where('id', $request->userId)->first();
            if ($user) {
                $user->status = $request->reason;
                info('user status is going to change on id : '.$user->id.' and status : '.$user->status);
                event(new UserStatusChanged($user->id, $user->status, $user->name));
                $user->save();
            }
        }

        if (isset($request->is_available)) {
            $updateLogString = $updateLogString.' is_available to : '.$request->is_available;
            $leadAllocationUser->is_available = $request->is_available;
        }

        if (isset($request->team_type) && $request->team_type == 'health' && isset($request->max_cap)) {
            $updateLogString = $updateLogString.' max_cap to : '.$request->max_cap;
            $leadAllocationUser->max_capacity = $request->max_cap;
        }

        $leadAllocationUser->save();
        $updateLogString = $updateLogString.' for user : '.$request->aid.' and by user : '.auth()->user()->id.' ----- ';
        info($updateLogString);
    }

    public function updateCaps(Request $request)
    {
        if (isset($request->max_cap)) {
            foreach ($request->max_cap as $item) {
                if ($item['userId'] && $item['maxCap']) {
                    $leadAllocationObj = LeadAllocation::with(['leadAllocationUser'])->where('user_id', $item['userId'])->first();
                    $leadAllocationObj->max_capacity = (int) $item['maxCap'];
                    $leadAllocationObj->save();
                    info('Updated max cap of user : '.$leadAllocationObj->leadAllocationUser->email.' to '.(int) $item['maxCap']);
                }

            }
        }
    }

    public function updateResetCapSwitch(Request $request)
    {
        if (isset($request->resetCap)) {
            $leadAllocationObj = LeadAllocation::with(['leadAllocationUser'])->where('user_id', $request->userId)->first();
            $leadAllocationObj->reset_cap = (int) $request->resetCap;
            $leadAllocationObj->save();
            info('Updated reset cap flag of user : '.$leadAllocationObj->leadAllocationUser->email.' to '.(int) $request->resetCap.' by user : '.auth()->user()->email);
        }
    }

    public function toggleLeadAllocationJobStatus()
    {
        $this->applicationStorageService->updateLeadAllocationJobStatus();
    }
    public function toggleCarLeadAllocationJobStatus()
    {
        $this->applicationStorageService->updateCarLeadAllocationJobStatus();
    }
    public function toggleRenewalCarLeadAllocationStatus()
    {
        $this->applicationStorageService->updateRenewalCarLeadAllocationStatus();
    }
    public function toggleCarLeadFetchSequence()
    {
        $this->applicationStorageService->updateCarLeadFetchSequence();
    }

    public function getTierUsers($tierId)
    {
        return $this->leadAllocationService->getTierUsersWithLeadAllocationRecord($tierId);
    }
}
