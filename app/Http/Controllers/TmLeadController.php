<?php

namespace App\Http\Controllers;

use App\Models\TmLead;
use Illuminate\Http\Request;
use DataTables;
use Auth;
use DB;
use App\Enums\tmLeadStatusCode;
use App\Services\TMLeadsService;
use App\Models\TmInsuranceType;
use App\Models\TmLeadStatus;
use App\Models\Nationality;
use App\Models\UAELicenseHeldFor;
use App\Models\Emirate;
use App\Models\User;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarTypeInsurance;
use App\Models\TmLeadType;

class TmLeadController extends Controller
{
    private $teleMarketingLeadsService;
    function __construct(TMLeadsService $tmLeadsCreateUpdateService)
    {
        $this->teleMarketingLeadsService = $tmLeadsCreateUpdateService;
        $this->middleware('permission:telemarketing-list|telemarketing-create|telemarketing-edit|telemarketing-delete', ['only' => ['index','store']]);
        $this->middleware('permission:telemarketing-create', ['only' => ['create','store']]);
        $this->middleware('permission:telemarketing-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:telemarketing-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $handlers = User::select('users.*')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','roles.id','model_has_roles.role_id')
        ->whereIn('roles.name', ['TM_ADVISOR'])->orderBy('roles.name', 'asc')->get();

        $tmInsuranceTypes = TmInsuranceType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmLeadTypes = TmLeadType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmLeadStatuses = TmLeadStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        if(Auth::user()->hasRole('TM_ADVISOR')) {
            $isCurrentUserIsAdvisor = "1";
        }
        else {
            $isCurrentUserIsAdvisor = "0";
        }

        if ($request->ajax()) {
            $queryTmLeads = TmLead::select('tm_leads.id as id','tm_leads.customer_name as customer_name'
            ,'tm_leads.notes as notes','tm_leads.enquiry_date as enquiry_date','tm_leads.allocation_date as allocation_date'
            ,'tm_leads.next_followup_date as next_followup_date','tm_leads.created_at as created_at'
            ,'tm_leads.updated_at as updated_at','tm_leads.cdb_id as cdb_id'
            ,'tm_lead_statuses.code as tm_lead_status_code','handlers.name as handlers_name'
            ,'tm_insurance_types.text as tm_insurance_types_text','tm_lead_statuses.text as tm_lead_status_text')
            ->leftjoin('tm_lead_statuses','tm_leads.tm_lead_statuses_id','tm_lead_statuses.id')
            ->leftjoin('users as handlers', 'tm_leads.assigned_to_id','handlers.id')
            ->leftjoin('tm_insurance_types', 'tm_leads.tm_insurance_types_id','tm_insurance_types.id')
            ->whereRaw('tm_leads.is_deleted=0')
            ->orderByRaw('tm_leads.next_followup_date IS NULL, tm_leads.next_followup_date, tm_leads.created_at');

            if(Auth::user()->hasRole('TM_ADVISOR')) {
                $queryTmLeads->where('tm_leads.assigned_to_id', Auth::user()->id);
            }

            if(isset($request->tm_lead_statuses_id) && !empty($request->tm_lead_statuses_id)) {
                $queryTmLeads->where('tm_leads.tm_lead_statuses_id', $request->tm_lead_statuses_id);
            }

            if (isset($request->searchType) && !empty($request->searchType)
            && isset($request->searchField) && !empty($request->searchField)) {
                if($request->searchType == 'cdbID') {
                    $queryTmLeads->where('tm_leads.cdb_id',$request->searchField);
                }
                else if($request->searchType == 'emailAddress') {
                    $queryTmLeads->where('tm_leads.email_address',$request->searchField);
                }
                else if($request->searchType == 'phoneNumber') {
                    $queryTmLeads->where('tm_leads.phone_number',$request->searchField);
                }
                else {
                    $queryTmLeads->where($request->searchType, $request->searchField);
                }
            }
            if (isset($request->searchType) && !empty($request->searchType)
            && isset($request->tmLeadsStartDate) && !empty($request->tmLeadsStartDate)
            && isset($request->tmLeadsEndDate) && !empty($request->tmLeadsEndDate)) {

                if($request->tmLeadsEndDate >= $request->tmLeadsStartDate) {
                    if($request->searchType == 'createdAt') {
                        $searchDateColumn = "created_at";
                    }
                    if($request->searchType == 'updatedAt') {
                        $searchDateColumn = "updated_at";
                    }
                    if($request->searchType == 'nextFollowupDate') {
                        $searchDateColumn = "next_followup_date";
                    }
                    if($request->searchType == 'enquiryDate') {
                        $searchDateColumn = "enquiry_date";
                    }
                    if($request->searchType == 'allocationDate') {
                        $searchDateColumn = "allocation_date";
                    }
                    $queryTmLeads->whereRaw('DATE(tm_leads.'.$searchDateColumn.') BETWEEN "'.$request->tmLeadsStartDate.'" AND "'.$request->tmLeadsEndDate.'"');
                }
            }
            if(isset($request->assigned_to_id) && !empty($request->assigned_to_id)) {
                if($request->assigned_to_id == "Unassigned") {
                    $queryTmLeads->where('tm_leads.assigned_to_id', '=', '')->orWhereNull('tm_leads.assigned_to_id');
                }
                else if($request->assigned_to_id == "MyLeads") {
                    $queryTmLeads->where('tm_leads.assigned_to_id', Auth::user()->id);
                }
                else {
                    $queryTmLeads->where('tm_leads.assigned_to_id', $request->assigned_to_id);
                }
            }
            if(isset($request->tm_insurance_types_id) && !empty($request->tm_insurance_types_id)) {
                $queryTmLeads->where('tm_leads.tm_insurance_types_id', $request->tm_insurance_types_id);
            }
            if(isset($request->tm_lead_types_id) && !empty($request->tm_lead_types_id)) {
                $queryTmLeads->where('tm_leads.tm_lead_types_id', $request->tm_lead_types_id);
            }

            return Datatables::of($queryTmLeads)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('tmlead.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('tmlead.view',compact("handlers","isCurrentUserIsAdvisor","tmInsuranceTypes"
        ,"tmLeadTypes","tmLeadStatuses"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tmLeadStatuses = TmLeadStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmInsuranceTypes = TmInsuranceType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $nationalities = Nationality::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $yearsOfDrivings = UAELicenseHeldFor::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carMakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carModels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $emiratesOfRegistrations = Emirate::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carTypeInsurances = CarTypeInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmLeadTypes = TmLeadType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $handlers = User::select('users.*')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','roles.id','model_has_roles.role_id')
        ->whereIn('roles.name', ['TM_ADVISOR', 'TM_DEPUTY', 'TM_MANAGER'])->orderBy('roles.name', 'asc')->get();

        if(Auth::user()->hasRole('TM_ADVISOR')) {
            $isUserTmAdvisor = "1";
        }
        else {
            $isUserTmAdvisor = "0";
        }

        return view('tmlead.add',compact('tmLeadStatuses','tmInsuranceTypes','handlers','nationalities'
        ,'yearsOfDrivings','carMakes','carModels','emiratesOfRegistrations','carTypeInsurances','tmLeadTypes','isUserTmAdvisor'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'customer_name' => 'required|max:50',
            'phone_number' => 'required|max:20',
            'email_address' => 'required|email|max:50',
            'tm_insurance_types_id' => 'required',
            'enquiry_date' => 'required',
            'allocation_date' => 'required',
            'tm_lead_types_id' => 'required',
        ]);

        $tmLeadID = $this->teleMarketingLeadsService->tmLeadsCreateUpdate($request,"create",$tmLeadID="");

        if(isset($request->return_to_view)) {
            return redirect("telemarketing/tmleads/".$tmLeadID)->with('success', 'TM Lead has been stored');
        }
        return redirect()->back()->with('success', 'TM Lead has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TmLead  $tmlead
     * @return \Illuminate\Http\Response
     */
    public function show(TmLead $tmlead)
    {
        $customerPhoneNo = $tmlead->phone_number;
        if(strlen($customerPhoneNo) == 9) { // 563264418 9
            $customerCorrectPhoneNo = "0".$customerPhoneNo;
        }
        else if(strlen($customerPhoneNo) == 12) { // 971563264418 12
            $customerPhoneNo = substr($customerPhoneNo, 3);
            $customerCorrectPhoneNo = "0".$customerPhoneNo;
        }
        else if(strlen($customerPhoneNo) == 13) {
            $customerPhoneNo = substr($customerPhoneNo, 0, 4);

            if($customerPhoneNo == "9710") { // 9710563264418 13
                $customerCorrectPhoneNo = substr($tmlead->phone_number, 3);
            }
            if($customerPhoneNo == "+971") { // +971563264418 13 Working
                $customerPhoneNo = substr($tmlead->phone_number, 4);
                $customerCorrectPhoneNo = "0".$customerPhoneNo;
            }
        }
        else if(strlen($customerPhoneNo) == 14) {
            $customerPhoneNo = substr($customerPhoneNo, 0, 5);

            if($customerPhoneNo == "00971") { // 00971563264418 14
                $customerCorrectPhoneNo = substr($tmlead->phone_number, 5);
                $customerCorrectPhoneNo = "0".$customerCorrectPhoneNo;
            }
            if($customerPhoneNo == "+9710") { // +9710563264418 14
                $customerCorrectPhoneNo = substr($tmlead->phone_number, 4);
            }

        }
        else if(strlen($customerPhoneNo) == 15) { // 009710563264418 15
            $customerCorrectPhoneNo = substr($customerPhoneNo, 5);
        }
        else {
            $customerCorrectPhoneNo = $customerPhoneNo; // 0563264418 10 Working
        }

        if(Auth::user()->hasRole("TM_ADVISOR")) {
            if(Auth::user()->id != $tmlead->assigned_to_id) {
                return redirect()->route("tmleads.index")->with("message","You don't have access to view this lead");
            }
        }

        $tmLeadStatusCode = TmLeadStatus::where('id', '=', $tmlead->tm_lead_statuses_id)->value('code');

        if( (($tmLeadStatusCode == tmLeadStatusCode::NoAnswer || $tmLeadStatusCode == tmLeadStatusCode::SwitchedOff) && $tmlead->no_answer_count == 3)
        || ($tmLeadStatusCode == tmLeadStatusCode::NotContactablePE || $tmLeadStatusCode == tmLeadStatusCode::CarSold
            || $tmLeadStatusCode == tmLeadStatusCode::NotEligible || $tmLeadStatusCode == tmLeadStatusCode::NotInterested
            || $tmLeadStatusCode == tmLeadStatusCode::PurchasedBeforeFirstCall || $tmLeadStatusCode == tmLeadStatusCode::PurchasedFromCompetitor
            || $tmLeadStatusCode == tmLeadStatusCode::RevivedByNewBusiness || $tmLeadStatusCode == tmLeadStatusCode::RevivedByRenewals
            || $tmLeadStatusCode == tmLeadStatusCode::WrongNumber || $tmLeadStatusCode == tmLeadStatusCode::DONOTCALL
            || $tmLeadStatusCode == tmLeadStatusCode::Duplicate || $tmLeadStatusCode == tmLeadStatusCode::Revived
            || $tmLeadStatusCode == tmLeadStatusCode::Recycled) ) {
                $isLeadEditable = "0";
        }
        else {
            $isLeadEditable = "1";
        }

        $tmLeadStatusCode = TmLeadStatus::where('id', '=', $tmlead->tm_lead_statuses_id)->value('code');
        $tmInsuranceTypeCode = TmInsuranceType::where('id', '=', $tmlead->tm_insurance_types_id)->value('code');
        $tmLeadStatuses = TmLeadStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        return view("tmlead.show",compact("tmlead","tmLeadStatusCode","tmInsuranceTypeCode"
        ,"tmLeadStatuses","isLeadEditable","customerCorrectPhoneNo"));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TmLead  $tmlead
     * @return \Illuminate\Http\Response
     */
    public function edit(TmLead $tmlead)
    {
        if(Auth::user()->hasRole("TM_ADVISOR")) {
            if(Auth::user()->id != $tmlead->assigned_to_id) {
                return redirect()->route("tmleads.index")->with("message","You don't have access to edit this lead");
            }
            $isUserTmAdvisor = "1";
        }
        else {
            $isUserTmAdvisor = "0";
        }

        $tmLeadStatuses = TmLeadStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmInsuranceTypes = TmInsuranceType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $nationalities = Nationality::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $yearsOfDrivings = UAELicenseHeldFor::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carMakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carModels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $emiratesOfRegistrations = Emirate::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carTypeInsurances = CarTypeInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmLeadTypes = TmLeadType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $handlers = User::select('users.*')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','roles.id','model_has_roles.role_id')
        ->whereIn('roles.name', ['TM_ADVISOR', 'TM_DEPUTY', 'TM_MANAGER'])->orderBy('roles.name', 'asc')->get();

        return view('tmlead.edit',compact('tmlead','tmLeadStatuses','tmInsuranceTypes','handlers','nationalities'
        ,'yearsOfDrivings','carMakes','carModels','emiratesOfRegistrations','carTypeInsurances','tmLeadTypes','isUserTmAdvisor'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TmLead  $tmlead
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TmLead $tmlead)
    {
        $this->validate($request,[
            'customer_name' => 'required|max:50',
            'phone_number' => 'required|max:20',
            'email_address' => 'required|email|max:50',
            'tm_insurance_types_id' => 'required',
            'enquiry_date' => 'required',
            'allocation_date' => 'required',
            'tm_lead_types_id' => 'required',
        ]);

        $tmLeadID = $this->teleMarketingLeadsService->tmLeadsCreateUpdate($request,"update",$tmlead->id);

        if(isset($request->return_to_view)) {
            return redirect("telemarketing/tmleads/".$tmLeadID)->with('success', 'TM Lead has been updated');
        }
        return redirect()->back()->with('success', 'TM Lead has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TmLead  $tmlead
     * @return \Illuminate\Http\Response
     */
    public function destroy(TmLead $tmlead)
    {
        $tmlead->is_deleted = 1;
        $tmlead->save();
        return redirect()->route("tmleads.index")->with("message", "TM Lead has been deleted");
    }

    public function carModelBasedOnCarMake(Request $request)
    {
        $make_code =$request->make_code;
        $carmodel = DB::table('car_model')->where('car_make_code','=',$make_code)->get(array('id','text','code'));
        return response()->json($carmodel);
    }

    public function tmLeadUpdate(Request $request)
    {
        $currentDateTime = date('Y-m-d H:i:s');
        $tmLeadStatusCode = TmLeadStatus::where('id', '=', $request->tm_lead_statuses_id)->value('code');

        $this->validate($request,[
            'tm_lead_statuses_id' => 'required',
            'notes' => 'max:500',
        ]);

        if( (($tmLeadStatusCode == tmLeadStatusCode::NoAnswer || $tmLeadStatusCode == tmLeadStatusCode::SwitchedOff) && $request->no_answer_count < "3")
        || ($tmLeadStatusCode == tmLeadStatusCode::PipelineNoInfo || $tmLeadStatusCode == tmLeadStatusCode::PipelineImmediate
            || $tmLeadStatusCode == tmLeadStatusCode::PipelineFuture || $tmLeadStatusCode == tmLeadStatusCode::DealingWithAnAdvisor) ) {
            $this->validate($request,[
                'next_followup_date' => 'required',
                'next_followup_date' => 'date_format:Y-m-d H:i:s|after_or_equal:'.$currentDateTime
            ]);
        }

        $tmLeadID = $this->teleMarketingLeadsService->tmLeadStatusNotesUpdate($request);

        if(Auth::user()->hasRole("TM_ADVISOR")) { // advisors redirection

            //$currentUserID = 27;
            $currentUserID = Auth::user()->id;

            $prioritizeLeadId = $this->teleMarketingLeadsService->tmLeadsGetPrioritizeLead($currentUserID);

            if($prioritizeLeadId) { // if more lead in queue
                return redirect("telemarketing/tmleads/".$prioritizeLeadId)
                ->with('success', 'Previous Lead#'.$tmLeadID.' updated. Please proceed with below queued lead');
            }
            else { // if more lead not in queue
                return redirect("telemarketing/tmleads")->with('success', 'No more leads for now!');
            }
        }
        else { // non-advisors redirection
            return redirect("telemarketing/tmleads/".$tmLeadID)->with('success', 'TM Lead has been updated');
        }

    }

    public function tmLeadsAssign(Request $request)
    {
        $assignedToUserIdNew = $this->teleMarketingLeadsService->tmLeadsUpdateAssignedTo($request);
        $assignedUserName = User::where('id', '=', $assignedToUserIdNew)->value('name');
        return redirect("telemarketing/tmleads")->with('success', 'TM Leads has been Assigned To '.$assignedUserName);
    }
}
