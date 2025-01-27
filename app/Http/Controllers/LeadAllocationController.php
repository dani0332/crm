<?php

namespace App\Http\Controllers;

use App\Enums\LeadAllocationUserBLStatusFiltersEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\TeamTypeEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\ReAssignCarLeadsJob;
use App\Jobs\ReAssignHealthLeadsJob;
use App\Jobs\ReAssignLeads;
use App\Models\LeadAllocation;
use App\Models\Team;
use App\Models\User;
use App\Services\ApplicationStorageService;
use App\Services\CarAllocationService;
use App\Services\CRUDService;
use App\Services\HealthAllocationService;
use App\Services\LeadAllocationService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LeadAllocationController extends Controller
{
    use TeamHierarchyTrait;

    protected $leadAllocationService;
    protected $applicationStorageService;
    protected $crudService;

    public function __construct(LeadAllocationService $leadAllocationService, ApplicationStorageService $applicationStorageService, CRUDService $crudService)
    {
        $this->leadAllocationService = $leadAllocationService;
        $this->applicationStorageService = $applicationStorageService;
        $this->crudService = $crudService;
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
            if (request()->has('userBlStatus') && request('userBlStatus') !== LeadAllocationUserBLStatusFiltersEnum::ALL->value) {
                $userBlStatus = LeadAllocationUserBLStatusFiltersEnum::from(request('userBlStatus'));
                $data = $userBlStatus->applyFilter($data);
            }
            foreach ($data as $key => $value) {
                $totalAssignedLeadCount += $value->allocation_count;
                if ($value->is_available == 1) {
                    $availableUsers++;
                } else {
                    $unAvailableUsers++;
                }
            }

            return inertia('LeadAllocation/Health', [
                'totalAssignedLeadCount' => $totalAssignedLeadCount,
                'availableUsers' => $availableUsers,
                'unAvailableUsers' => $unAvailableUsers,
                'isAutoAllocationWorking' => (int) $isAutoAllocationWorking,
                'data' => $data,
                'quoteType' => QuoteTypes::HEALTH->value,
                'userBLStatuses' => LeadAllocationUserBLStatusFiltersEnum::withLabels(),
            ]);
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
        $quoteTypeId = QuoteTypes::getIdFromValue(request('quoteType')) ?? null;
        $quoteTypeId = in_array(request('quoteType'), [quoteTypeCode::CORPLINE, quoteTypeCode::GroupMedical]) ? QuoteTypeId::Business : $quoteTypeId;
        foreach ($request->all() as $item) {
            if ($quoteTypeId) {
                $leadAllocationUser = LeadAllocation::where('user_id', $item['userId'])->where('quote_type_id', $quoteTypeId)->where('id', $item['id'])->first();
                if ($leadAllocationUser) {
                    if (isset($item['reason'])) {
                        if ($item['reason'] != UserStatusEnum::OFFLINE && $item['reason'] != UserStatusEnum::ONLINE) {
                            info('User status is going to change to : '.UserStatusEnum::getUserStatusText($item['reason']));
                            $car = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Car)->first();
                            if ($this->userHaveProduct($item['userId'], $car?->id)) {
                                info('user belong to car so dispatching car reassignment job');
                                dispatch(new ReAssignCarLeadsJob(app(CarAllocationService::class), $item['userId']));
                            }

                            $health = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Health)->first();
                            if ($this->userHaveProduct($item['userId'], $health?->id)) {
                                info('user belong to health so dispatching health reassignment job');
                                dispatch(new ReAssignHealthLeadsJob(app(HealthAllocationService::class), $item['userId']));
                            }

                            foreach ([QuoteTypes::CORPLINE, QuoteTypes::LIFE, QuoteTypes::HOME, QuoteTypes::PET, QuoteTypes::YACHT, QuoteTypes::CYCLE] as $quoteType) {
                                $team = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', $quoteType->value)->first();
                                if ($this->userHaveProduct($item['userId'], $team?->id)) {
                                    info("user belongs to {$quoteType->value} so dispatching {$quoteType->value} reassignment job");
                                    ReAssignLeads::dispatch($quoteType, $item['userId']);
                                }
                            }
                        }

                        $user = User::where('id', $item['userId'])->first();
                        if ($user) {
                            $user->status = $item['reason'];
                            info('user status is going to change on id : '.$user->id.' and status : '.$user->status);
                            $user->save();
                        }
                    }

                    if (isset($item['is_available'])) {
                        $updateLogString = $updateLogString.' is_available to : '.$item['is_available'];
                        $leadAllocationUser->is_available = $item['is_available'];
                    }

                    if (isset($item['max_cap'])) {
                        $updateLogString = $updateLogString.' max_cap to : '.$item['max_cap'];
                        if ($item['type'] === 'buy-lead') {
                            $leadAllocationUser->buy_lead_max_capacity = (int) $item['max_cap'];
                        } else {
                            $leadAllocationUser->max_capacity = (int) $item['max_cap'];
                        }
                    }

                    $leadAllocationUser->save();
                }

                $updateLogString = $updateLogString.' for user : '.$item['userId'].' and by user : '.auth()->user()->id.' ----- ';
                info($updateLogString);
            }
        }
    }

    public function updateCaps(Request $request)
    {
        if (isset($request->max_cap)) {
            $quoteTypeId = QuoteTypes::getIdFromValue(request('quoteType')) ?? null;
            $quoteTypeId = in_array(request('quoteType'), [quoteTypeCode::CORPLINE, quoteTypeCode::GroupMedical]) ? QuoteTypeId::Business : $quoteTypeId;
            foreach ($request->max_cap as $item) {
                if ($item['userId'] && $item['maxCap'] && $quoteTypeId) {
                    $leadAllocationObj = LeadAllocation::with(['leadAllocationUser'])->where('quote_type_id', $quoteTypeId)->where('user_id', $item['userId'])->first();

                    if ($leadAllocationObj) {
                        if ($request->type === 'buy-lead') {
                            $leadAllocationObj->buy_lead_max_capacity = (int) $item['maxCap'];
                        } else {
                            $leadAllocationObj->max_capacity = (int) $item['maxCap'];
                        }
                        $leadAllocationObj->save();
                        info('Updated max cap of user : '.$leadAllocationObj->leadAllocationUser->email.' to '.(int) $item['maxCap']);
                    } else {
                        info('No LeadAllocation record found for user : '.$item['userId']);
                    }
                }
            }

            return response()->json([
                'message' => 'Max Capacity Updated Successfully.',
            ], 200);
        } else {
            return back()->with('info', 'Please select at least one item.');
        }
    }

    public function updateResetCapSwitch(Request $request)
    {
        $requester = auth()->user();
        info(self::class."::updateResetCapSwitch - Requester: {$requester->id}: {$requester->name} ({$requester->email})".json_encode($request->all()));

        if (isset($request->resetCap)) {
            $leadAllocationObj = LeadAllocation::latest()->with(['leadAllocationUser']);
            if (isset($request->leadId)) {
                $leadAllocationObj = $leadAllocationObj->where('id', $request->leadId);
            } else {
                $leadAllocationObj = $leadAllocationObj->where('user_id', $request->userId);
            }
            $leadAllocationObj = $leadAllocationObj->first();
            $leadAllocationObj->reset_cap = (int) $request->resetCap;
            $leadAllocationObj->save();
            info('Updated reset cap flag of user : '.$leadAllocationObj->leadAllocationUser->email.' to '.(int) $request->resetCap.' by user : '.auth()->user()->email);
        }
    }

    public function updateBlStatus(Request $request)
    {
        $requester = auth()->user();
        info(self::class."::updateBlStatus - Requester: {$requester->id}: {$requester->name} ({$requester->email})".json_encode($request->all()));
        if (isset($request->buyLeadStatus)) {
            $leadAllocationObj = LeadAllocation::latest()->with(['leadAllocationUser']);
            if (isset($request->leadId)) {
                $leadAllocationObj = $leadAllocationObj->where('id', $request->leadId);
            } else {
                $leadAllocationObj = $leadAllocationObj->where('user_id', $request->userId);
            }
            $leadAllocationObj = $leadAllocationObj->first();
            $leadAllocationObj->buy_lead_status = (int) $request->buyLeadStatus;
            $leadAllocationObj->save();
        }
    }

    public function updateNormalLeadAllocationStatus(Request $request)
    {
        $requester = auth()->user();
        info(self::class."::updateNormalLeadAllocationStatus - Requester: {$requester->id}: {$requester->name} ({$requester->email})".json_encode($request->all()));
        if (isset($request->nlStatus)) {
            $leadAllocationObj = LeadAllocation::latest()->with(['leadAllocationUser']);
            if (isset($request->laId)) {
                $leadAllocationObj = $leadAllocationObj->where('id', $request->laId);
            } else {
                $leadAllocationObj = $leadAllocationObj->where('user_id', $request->userId);
            }
            $leadAllocationObj = $leadAllocationObj->first();
            $leadAllocationObj->normal_allocation_enabled = (int) $request->nlStatus;
            $leadAllocationObj->save();
        }
    }

    public function updateBLResetCap(Request $request)
    {
        $requester = auth()->user();
        info(self::class."::updateBLResetCap - Requester: {$requester->id}: {$requester->name} ({$requester->email})".json_encode($request->all()));
        if (isset($request->blResetCap)) {
            $leadAllocationObj = LeadAllocation::latest()->with(['leadAllocationUser']);
            if (isset($request->laId)) {
                $leadAllocationObj = $leadAllocationObj->where('id', $request->laId);
            } else {
                $leadAllocationObj = $leadAllocationObj->where('user_id', $request->userId);
            }
            $leadAllocationObj = $leadAllocationObj->first();
            $leadAllocationObj->buy_lead_reset_capacity = (int) $request->blResetCap;
            $leadAllocationObj->save();
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
