<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\TypeOfInsurance;
use App\Models\SubTypeOfInsurance;
use App\Models\ClaimsStatus;
use App\Models\CarRepairCoverage;
use App\Models\CarRepairType;
use App\Models\RentACar;
use App\Models\User;
use App\Models\CarMake;
use App\Models\CarModel;

use Illuminate\Http\Request;
use Auth;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;

class ClaimController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    function __construct()
    {
         $this->middleware('permission:claim-list|claim-create|claim-edit|claim-delete', ['only' => ['index','store']]);
         $this->middleware('permission:claim-create', ['only' => ['create','store']]);
         $this->middleware('permission:claim-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:claim-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Claim::select('*');
            return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        return view('claim.actions', compact('row'))->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        
        return view('claim.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $typeofinsurances = TypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $subtypeofinsurances = SubTypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $claimsstatuses = ClaimsStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carrepaircoverages = CarRepairCoverage::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carrepairtypes = CarRepairType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $rentacars = RentACar::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $advisors = User::all();
        $carmakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carmodels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        return view('claim.add',compact('typeofinsurances','subtypeofinsurances','claimsstatuses',
        'carrepaircoverages','carrepairtypes','rentacars','advisors','carmakes','carmodels'));
        //return view('claim.add');
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
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email_address' => 'required|max:150',
            'phone_number' => 'required|max:20',
            'insurance_company' => 'required|max:255',
            'policy_number' => 'required|max:150',
            //'additional_notes' => 'required|max:2000',
            'type_of_insurances_id' => 'required',
            'claims_status_id' => 'required',
            //'car_repair_coverage_id' => 'required',
            //'car_repair_type_id' => 'required',
            //'rent_a_car_id' => 'required',
            //'assigned_to_id' => 'required',
            'attachment_1' => 'mimes:jpg,jpeg,png,bmp,pdf,docx|max:5120',
            'attachment_2' => 'mimes:jpg,jpeg,png,bmp,pdf,docx|max:5120',
            'attachment_3' => 'mimes:jpg,jpeg,png,bmp,pdf,docx|max:5120',
            'attachment_4' => 'mimes:jpg,jpeg,png,bmp,pdf,docx|max:5120',
        ]);

        $claim = new Claim();
        $claim->first_name =  $request->first_name;
        $claim->last_name =  $request->last_name;
        $claim->email_address =  $request->email_address;
        $claim->phone_number =  $request->phone_number;
        $claim->insurance_company =  $request->insurance_company;
        $claim->policy_number =  $request->policy_number;
        $claim->additional_notes =  $request->additional_notes;
        $claim->ticket_number =  $request->ticket_number;
        $claim->plate_number =  $request->plate_number;
        $claim->standard_excess_payable =  $request->standard_excess_payable;
        $claim->liability =  $request->liability;
        $claim->workshop =  $request->workshop;
        $claim->insurer_reference =  $request->insurer_reference;
        $claim->date_of_loss =  $request->date_of_loss;
        $claim->claim_amount =  $request->claim_amount;
        $claim->type_of_insurances_id = $request->type_of_insurances_id;
        $claim->sub_type_of_insurance_id = $request->sub_type_of_insurance_id;
        $claim->claims_status_id = $request->claims_status_id;
        $claim->car_repair_coverage_id = $request->car_repair_coverage_id;
        $claim->car_repair_type_id = $request->car_repair_type_id;
        $claim->rent_a_car_id = $request->rent_a_car_id;
        $claim->assigned_to_id = $request->assigned_to_id;
        $claim->car_make_id = $request->car_make_id;
        $claim->car_model_id = $request->car_model_id;
        $claim->created_by_id = Auth::user()->id;
        $claim->modified_by_id = Auth::user()->id;

        if($request->hasFile('attachment_1')) {
            $fileName_1 = time() . '_' . $request->attachment_1->getClientOriginalName();
            // save file to azure blob virtual directory uplaods in your container
            $filePath = $request->file('attachment_1')->storeAs('/', $fileName_1, 'azure');
            $claim->attachment_1 = $fileName_1;
        }
        if($request->hasFile('attachment_2')) {
            $fileName_2 = time() . '_' . $request->attachment_2->getClientOriginalName();
            // save file to azure blob virtual directory uplaods in your container
            $filePath = $request->file('attachment_2')->storeAs('/', $fileName_2, 'azure');
            $claim->attachment_2 = $fileName_2;
        }
        if($request->hasFile('attachment_3')) {
            $fileName_3 = time() . '_' . $request->attachment_3->getClientOriginalName();
            // save file to azure blob virtual directory uplaods in your container
            $filePath = $request->file('attachment_3')->storeAs('/', $fileName_3, 'azure');
            $claim->attachment_3 = $fileName_3;
        }
        if($request->hasFile('attachment_4')) {
            $fileName_4 = time() . '_' . $request->attachment_4->getClientOriginalName();
            // save file to azure blob virtual directory uplaods in your container
            $filePath = $request->file('attachment_4')->storeAs('/', $fileName_4, 'azure');
            $claim->attachment_4 = $fileName_4;
        }

        $claim->save();
        if(isset($request->return_to_view))
            return redirect("claim/claims");
        return back()->with('success','Claim has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Claim  $claim
     * @return \Illuminate\Http\Response
     */
    public function show(Claim $claim)
    {
        return view('claim.show',compact('claim'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Claim  $claim
     * @return \Illuminate\Http\Response
     */
    public function edit(Claim $claim)
    {
        $typeofinsurances = TypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $subtypeofinsurances = SubTypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $claimsstatuses = ClaimsStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carrepaircoverages = CarRepairCoverage::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carrepairtypes = CarRepairType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $rentacars = RentACar::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $advisors = User::all();
        $carmakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carmodels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        return view('claim.edit',compact('typeofinsurances','claim','subtypeofinsurances','claimsstatuses',
        'carrepaircoverages','carrepairtypes','rentacars','advisors','carmakes','carmodels'));
        //return view('claim.edit',compact('claim'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Claim  $claim
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Claim $claim)
    {
        $this->validate($request,[
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email_address' => 'required|max:150',
            'phone_number' => 'required|max:20',
            'insurance_company' => 'required|max:255',
            'policy_number' => 'required|max:150',
            //'additional_notes' => 'required|max:2000',
            'type_of_insurances_id' => 'required',
            'claims_status_id' => 'required',
            //'car_repair_coverage_id' => 'required',
            //'car_repair_type_id' => 'required',
            //'rent_a_car_id' => 'required',
            //'assigned_to_id' => 'required',
            'attachment_1' => 'mimes:jpg,jpeg,png,bmp,pdf,docx|max:5120',
            'attachment_2' => 'mimes:jpg,jpeg,png,bmp,pdf,docx|max:5120',
            'attachment_3' => 'mimes:jpg,jpeg,png,bmp,pdf,docx|max:5120',
            'attachment_4' => 'mimes:jpg,jpeg,png,bmp,pdf,docx|max:5120',
        ]);
        $claim->first_name =  $request->first_name;
        $claim->last_name =  $request->last_name;
        $claim->email_address =  $request->email_address;
        $claim->phone_number =  $request->phone_number;
        $claim->insurance_company =  $request->insurance_company;
        $claim->policy_number =  $request->policy_number;
        $claim->additional_notes =  $request->additional_notes;
        $claim->ticket_number =  $request->ticket_number;
        $claim->plate_number =  $request->plate_number;
        $claim->standard_excess_payable =  $request->standard_excess_payable;
        $claim->liability =  $request->liability;
        $claim->workshop =  $request->workshop;
        $claim->insurer_reference =  $request->insurer_reference;
        $claim->date_of_loss =  $request->date_of_loss;
        $claim->claim_amount =  $request->claim_amount;
        $claim->type_of_insurances_id = $request->type_of_insurances_id;
        $claim->sub_type_of_insurance_id = $request->sub_type_of_insurance_id;
        $claim->claims_status_id = $request->claims_status_id;
        $claim->car_repair_coverage_id = $request->car_repair_coverage_id;
        $claim->car_repair_type_id = $request->car_repair_type_id;
        $claim->rent_a_car_id = $request->rent_a_car_id;
        $claim->assigned_to_id = $request->assigned_to_id;
        $claim->car_make_id = $request->car_make_id;
        $claim->car_model_id = $request->car_model_id;
        $claim->modified_by_id = Auth::user()->id;

        if($request->hasFile('attachment_1')) {
            $fileName_1 = time() . '_' . $request->attachment_1->getClientOriginalName();
            // save file to azure blob virtual directory uplaods in your container
            $filePath = $request->file('attachment_1')->storeAs('/', $fileName_1, 'azure');
            $claim->attachment_1 = $fileName_1;
        }
        if($request->hasFile('attachment_2')) {
            $fileName_2 = time() . '_' . $request->attachment_2->getClientOriginalName();
            // save file to azure blob virtual directory uplaods in your container
            $filePath = $request->file('attachment_2')->storeAs('/', $fileName_2, 'azure');
            $claim->attachment_2 = $fileName_2;
        }
        if($request->hasFile('attachment_3')) {
            $fileName_3 = time() . '_' . $request->attachment_3->getClientOriginalName();
            // save file to azure blob virtual directory uplaods in your container
            $filePath = $request->file('attachment_3')->storeAs('/', $fileName_3, 'azure');
            $claim->attachment_3 = $fileName_3;
        }
        if($request->hasFile('attachment_4')) {
            $fileName_4 = time() . '_' . $request->attachment_4->getClientOriginalName();
            // save file to azure blob virtual directory uplaods in your container
            $filePath = $request->file('attachment_4')->storeAs('/', $fileName_4, 'azure');
            $claim->attachment_4 = $fileName_4;
        }

        $claim->save();
        if(isset($request->return_to_view))
            return redirect("claim/claims");
        return back()->with('success','Claim has been Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Claim  $claim
     * @return \Illuminate\Http\Response
     */
    public function destroy(Claim $claim)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $claim->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        return redirect()->route('claims.index');
    }

    public function carModelBasedOnCarMake(Request $request){
        $make_code =$request->make_code;
        $carmodel = DB::table('car_model')->where('car_make_code','=',$make_code)->get(array('id','text','code'));
        return response()->json($carmodel);
    }
}
