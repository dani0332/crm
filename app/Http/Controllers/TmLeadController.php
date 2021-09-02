<?php

namespace App\Http\Controllers;

use App\Models\TmLead;
use Illuminate\Http\Request;
use DataTables;
use App\Models\TmCallStatus;
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
use App\Enums\tmInsuranceTypeCode;
use Auth;
use App\Services\TMLeadsService;

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
        if ($request->ajax()) {
            $data = TmLead::select('tm_leads.*','tm_call_statuses.text as tm_call_statuses_text'
            ,'tm_lead_statuses.text as tm_lead_statuses_text','handlers.name as handlers_name'
            ,'tm_insurance_types.text as tm_insurance_types_text')
            ->leftjoin('tm_call_statuses','tm_leads.tm_call_statuses_id','tm_call_statuses.id')
            ->leftjoin('tm_lead_statuses','tm_leads.tm_lead_statuses_id','tm_lead_statuses.id')
            ->leftjoin('users as handlers', 'tm_leads.assigned_to_id','handlers.id')
            ->leftjoin('tm_insurance_types', 'tm_leads.tm_insurance_types_id','tm_insurance_types.id')
            ->where('tm_leads.is_deleted', 0)
            ->orderBy('tm_leads.created_at','desc');

            if (isset($request->searchType) && !empty($request->searchType)
            && isset($request->searchField) && !empty($request->searchField)) {
                if($request->searchType == 'cdbID') {
                    $data->where('tm_leads.cdb_id',$request->searchField);
                }
                else if($request->searchType == 'emailAddress') {
                    $data->where('tm_leads.email_address',$request->searchField);
                }
                else if($request->searchType == 'phoneNumber') {
                    $data->where('tm_leads.phone_number',$request->searchField);
                }
                else {
                    $data->where($request->searchType, $request->searchField);
                }
            }

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('tmlead.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('tmlead.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tmCallStatuses = TmCallStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmLeadStatuses = TmLeadStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmInsuranceTypes = TmInsuranceType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $handlers = User::all();
        $nationalities = Nationality::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $yearsOfDrivings = UAELicenseHeldFor::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carMakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carModels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $emiratesOfRegistrations = Emirate::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carTypeInsurances = CarTypeInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmLeadTypes = TmLeadType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        return view('tmlead.add',compact('tmCallStatuses','tmLeadStatuses','tmInsuranceTypes','handlers','nationalities'
        ,'yearsOfDrivings','carMakes','carModels','emiratesOfRegistrations','carTypeInsurances','tmLeadTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $tmCallStatusCode = TmCallStatus::where('id', '=', $request->tm_call_statuses_id)->value('code');

        $this->validate($request,[
            'customer_name' => 'required|max:50',
            'phone_number' => 'required|max:20',
            'email_address' => 'required|email|max:50',
            'tm_insurance_types_id' => 'required',
            'enquiry_date' => 'required',
            'allocation_date' => 'required',
            'tm_call_statuses_id' => 'required',
            'tm_lead_statuses_id' => 'required',
            'tm_lead_types_id' => 'required',
        ]);

        if(($tmCallStatusCode == "Callback" || $tmCallStatusCode == "No Answer") && $request->no_answer_count < "3") {
            $this->validate($request,[
                'next_followup_date' => 'required',
            ]);
        }

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
        return view('tmlead.show',compact('tmlead'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TmLead  $tmlead
     * @return \Illuminate\Http\Response
     */
    public function edit(TmLead $tmlead)
    {
        $tmCallStatuses = TmCallStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmLeadStatuses = TmLeadStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmInsuranceTypes = TmInsuranceType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $handlers = User::all();
        $nationalities = Nationality::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $yearsOfDrivings = UAELicenseHeldFor::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carMakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carModels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $emiratesOfRegistrations = Emirate::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carTypeInsurances = CarTypeInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $tmLeadTypes = TmLeadType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        return view('tmlead.edit',compact('tmlead','tmCallStatuses','tmLeadStatuses','tmInsuranceTypes','handlers','nationalities'
        ,'yearsOfDrivings','carMakes','carModels','emiratesOfRegistrations','carTypeInsurances','tmLeadTypes'));
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
        $tmCallStatusCode = TmCallStatus::where('id', '=', $request->tm_call_statuses_id)->value('code');

        $this->validate($request,[
            'customer_name' => 'required|max:50',
            'phone_number' => 'required|max:20',
            'email_address' => 'required|email|max:50',
            'tm_insurance_types_id' => 'required',
            'enquiry_date' => 'required',
            'allocation_date' => 'required',
            'tm_call_statuses_id' => 'required',
            'tm_lead_statuses_id' => 'required',
            'tm_lead_types_id' => 'required',
        ]);

        if(($tmCallStatusCode == "Callback" || $tmCallStatusCode == "No Answer") && $request->no_answer_count < "3") {
            $this->validate($request,[
                'next_followup_date' => 'required',
            ]);
        }

        $tmLeadID = $this->teleMarketingLeadsService->tmLeadsCreateUpdate($request,"update",$tmlead->id);

        if(isset($request->return_to_view)) {
            return redirect("telemarketing/tmleads/".$tmLeadID)->with('success', 'TM Lead has been stored');
        }
        return redirect()->back()->with('success', 'TM Lead has been stored');
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
}
