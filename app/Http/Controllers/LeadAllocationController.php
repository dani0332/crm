<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Enums\quoteTypeCode;
use Illuminate\Http\Request;
use App\Enums\UserStatusEnum;
use App\Models\LeadAllocation;
use App\Events\UserStatusChanged;
use App\Jobs\ReAssignCarLeadsJob;
use App\Traits\TeamHierarchyTrait;
use App\Jobs\ReAssignHealthLeadsJob;
use Illuminate\Support\Facades\Gate;
use App\Services\CarAllocationService;
use App\Services\LeadAllocationService;
use App\Repositories\QuoteTypeRepository;
use App\Services\HealthAllocationService;
use App\Services\ApplicationStorageService;

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

            $unAssignedGood = $this->leadAllocationService->getUnAssignedHealthQuotes(TeamNameEnum::RM_SPEED);
            $unAssignedBest = $this->leadAllocationService->getUnAssignedHealthQuotes(TeamNameEnum::RM_NB);
            $unAssignedEntryLevel = $this->leadAllocationService->getUnAssignedHealthQuotes(TeamNameEnum::EBP);
     
   
            return inertia('LeadAllocation/Health', [
                'totalAssignedLeadCount' => $totalAssignedLeadCount,
                'availableUsers' => $availableUsers,
                'unAvailableUsers' => $unAvailableUsers,
                'isAutoAllocationWorking' => (int) $isAutoAllocationWorking,
                'data' => $data,
                'unAssignedGood' => $unAssignedGood,
                'unAssignedBest' => $unAssignedBest,
                'quoteType' => QuoteTypes::HEALTH->value,
                'unAssignedEntryLevel' => $unAssignedEntryLevel,
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

        foreach ($request->all() as $item) {
            $leadAllocationUser = LeadAllocation::where('user_id', $item['userId'])->where('id', $item['id'])->first();
            if (isset($item['reason'])) {
          
                if ($item['reason'] != UserStatusEnum::OFFLINE && $item['reason'] != UserStatusEnum::ONLINE) {
          
                    $car = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Car)->first();
                    $health = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', quoteTypeCode::Health)->first();
                    if ($this->userHaveProduct($item['userId'], $car->id)) {
                        info('user belong to car so dispatching car reassignment job');
                        dispatch(new ReAssignCarLeadsJob(app(CarAllocationService::class), $item['userId']));
                    }
                    if ($this->userHaveProduct($item['userId'], $health->id)) {
                        info('user belong to health so dispatching health reassignment job');
                        dispatch(new ReAssignHealthLeadsJob(app(HealthAllocationService::class), $item['userId']));
                    }
                }

                $user = User::where('id', $item['userId'])->first();
                if ($user) {
                    $user->status = $item['reason'];
                    info('user status is going to change on id : '.$user->id.' and status : '.$user->status);
                    event(new UserStatusChanged($user->id, $user->status, $user->name));
                    $user->save();
                }
            }

            if (isset($item['is_available'])) {
       
                $updateLogString = $updateLogString.' is_available to : '.$item['is_available'];
                $leadAllocationUser->is_available = $item['is_available'];
            }

            $quote_type_id=$this->getQuoteType($request->quoteType) ?? null;

            if (!empty($quote_type_id) && isset($item['max_cap'])) {
    
                $updateLogString = $updateLogString.' max_cap to : '.$item['max_cap'];
                $leadAllocationUser->max_capacity = (int)$item['max_cap'];
                $leadAllocationUser->quote_type_id = $quote_type_id;

            }

            $leadAllocationUser->save();
            $updateLogString = $updateLogString.' for user : '.$item['userId'].' and by user : '.auth()->user()->id.' ----- ';
            info($updateLogString);
        }
    }

    public function getQuoteType($val)
    {
        switch (ucfirst($val)) {
            case QuoteTypes::CAR->value:
                return QuoteTypes::CAR->id();

            case QuoteTypes::HEALTH->value:
                return QuoteTypes::HEALTH->id();

        }
    }
    public function updateCaps(Request $request)
    {   
     
     
        if (isset($request->max_cap)) {
            $quote_type_id=$this->getQuoteType($request->quoteType) ?? null;
            foreach ($request->max_cap as $item) {
                if ($item['userId'] && $item['maxCap']) {
                    $leadAllocationObj = LeadAllocation::with(['leadAllocationUser'])->where('user_id', $item['userId'])->first();
                    $leadAllocationObj->max_capacity = (int) $item['maxCap'];
                    $leadAllocationObj->quote_type_id = $quote_type_id;
                    $leadAllocationObj->save();
                    info('Updated max cap of user : '.$leadAllocationObj->leadAllocationUser->email.' to '.(int) $item['maxCap']);
                }
            }
        }
    }

    public function updateResetCapSwitch(Request $request)
    {
        if (isset($request->resetCap)) {
            $leadAllocationObj = LeadAllocation::latest()->with(['leadAllocationUser']);
            if(isset($request->lead_id)){
                $leadAllocationObj =  $leadAllocationObj->where('id',$request->lead_id);
            }
            else {
                $leadAllocationObj =  $leadAllocationObj->where('user_id', $request->userId);
            }
            $leadAllocationObj = $leadAllocationObj->first();
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
    

    public function showLeadAllocations(Request $request){
         if (Gate::allows('advisors-lead-allocation-caps', auth()->user())) {
            $data = $this->leadAllocationService->getAllocationLeads();
            $quoteTypes = QuoteTypeRepository::GetList();
    
          
      
            return inertia('LeadAllocation/AdvisorsLeadCaps', [
                'quoteTypes' => $quoteTypes,
                'allocations_leads' =>  $data,
                'advisors'=> $this->getHealthAndMotorAdvisorsList(),
            ]);
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function storeLeadAllocation(Request $request){
      
       $validateDate =(object) $request->validate([
            'quote_type_id' => 'required',
            'user_id' => 'required',
            'max_capacity' => 'required|integer',
            
        ]);

        $is_lead = $this->leadAllocationService->getLeadAllocationRecordByUserId($request->user_id,$request->quote_type_id);
       
        if(!empty($is_lead))
           return back()->with('error','Advisor already has an assigned capacity value for this quote type');
        
        $new_lead = $this->leadAllocationService->createLeadAllocationRecord($request->user_id, $validateDate );
      

        return redirect(route('lead.allocations.index'))->with('message', 'Advisor capacity assigned successfully');
       
        
    }
    
    public function deleteLeadAllocation($id){
        
        $is_delete = $this->leadAllocationService->deleteAllocationLead($id);
        
        if($is_delete){

            return back()->with('success','Lead allocation deleted successfully');
        }

    }
    

    public function getHealthAndMotorAdvisorsList(){
        
        $health_advisors = $this->leadAllocationService->getAdvisorsByModelType(QuoteTypes::HEALTH->value);
        $motor_advisors = $this->leadAllocationService->getAdvisorsByModelType(QuoteTypes::CAR->value);

        $advisors_collection = collect([$health_advisors,$motor_advisors]);
        $advisors_collapsed = $advisors_collection->collapse();
        $advisors = $advisors_collapsed->unique()->values()->all();

        return $advisors;
    }

    public function createLeadAllocation(Request $request){
        
        $quoteTypes = QuoteTypeRepository::GetList();
         
        return inertia('LeadAllocation/CreateAdvisorsLeadCaps', [
            'quoteTypes' => $quoteTypes,
            'advisors'=> $this->getHealthAndMotorAdvisorsList(),
        ]);
    }


    public function updateCapsLeadAllocation(Request $request){
        
        if (isset($request->items)) {
           
            foreach ($request->items as $item) {
           
                if ($item['userId'] && $item['maxCap']) {
                    $leadAllocationObj = LeadAllocation::with(['leadAllocationUser'])->where('user_id', $item['userId'])->first();
                    $leadAllocationObj->max_capacity = (int) $item['maxCap'];
                    $leadAllocationObj->save();
                    info('Updated max cap of user : '.$leadAllocationObj->leadAllocationUser->email.' to '.(int) $item['maxCap']);
                }
            }
        }

        return  redirect(route('lead.allocations.index'))->with('message', 'Advisor capacity assigned successfully');
    }

}
