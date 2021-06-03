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
        $typeofinsurances = TypeOfInsurance::all();
        $subtypeofinsurances = SubTypeOfInsurance::all();
        $claimsstatuses = ClaimsStatus::all();
        $carrepaircoverages = CarRepairCoverage::all();
        $carrepairtypes = CarRepairType::all();
        $rentacars = RentACar::all();
        $advisors = User::all();
        $carmakes = CarMake::all();
        $carmodels = CarModel::all();
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
            'additional_notes' => 'required|max:2000',
            'typeofinsurance_id' => 'required',
            'claimsstatus_id' => 'required',
            'carrepaircoverage_id' => 'required',
            'carrepairtype_id' => 'required',
            'rentacar_id' => 'required',
            'assigned_to_id' => 'required',
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
        $claim->typeofinsurance_id = $request->typeofinsurance_id;
        $claim->subtypeofinsurance_id = $request->subtypeofinsurance_id;
        $claim->claimsstatus_id = $request->claimsstatus_id;
        $claim->carrepaircoverage_id = $request->carrepaircoverage_id;
        $claim->carrepairtype_id = $request->carrepairtype_id;
        $claim->rentacar_id = $request->rentacar_id;
        $claim->assigned_to_id = $request->assigned_to_id;
        $claim->car_make_id = $request->car_make_id;
        $claim->car_model_id = $request->car_model_id;
        $claim->save();
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
        $typeofinsurances = TypeOfInsurance::all();
        $subtypeofinsurances = SubTypeOfInsurance::all();
        $claimsstatuses = ClaimsStatus::all();
        $carrepaircoverages = CarRepairCoverage::all();
        $carrepairtypes = CarRepairType::all();
        $rentacars = RentACar::all();
        $advisors = User::all();
        $carmakes = CarMake::all();
        $carmodels = CarModel::all();
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
            'additional_notes' => 'required|max:2000',
            'claimsstatus_id' => 'required',
            'carrepaircoverage_id' => 'required',
            'carrepairtype_id' => 'required',
            'rentacar_id' => 'required',
            'assigned_to_id' => 'required',
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
        $claim->typeofinsurance_id = $request->typeofinsurance_id;
        $claim->subtypeofinsurance_id = $request->subtypeofinsurance_id;
        $claim->claimsstatus_id = $request->claimsstatus_id;
        $claim->carrepaircoverage_id = $request->carrepaircoverage_id;
        $claim->carrepairtype_id = $request->carrepairtype_id;
        $claim->rentacar_id = $request->rentacar_id;
        $claim->assigned_to_id = $request->assigned_to_id;
        $claim->car_make_id = $request->car_make_id;
        $claim->car_model_id = $request->car_model_id;
        $claim->save();
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
        $claim->delete();
        return back()->with('success','Claim has been deleted');
    }
}
