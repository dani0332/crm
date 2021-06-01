<?php

namespace App\Http\Controllers;

use App\Models\Claim;
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
        return view('claim.add');
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
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'email_address' => 'required|max:120',
            'phone_number' => 'required|max:120',
            'insurance_company' => 'required|max:120',
            'insurance_type' => 'required|max:120',
            'policy_number' => 'required|max:120',
            'basic_details' => 'required|max:3000',
        ]);

        $claim = new Claim();
        $claim->first_name =  $request->first_name;
        $claim->last_name =  $request->last_name;
        $claim->email_address =  $request->email_address;
        $claim->phone_number =  $request->phone_number;
        $claim->insurance_company =  $request->insurance_company;
        $claim->insurance_type =  $request->insurance_type;
        $claim->policy_number =  $request->policy_number;
        $claim->basic_details =  $request->basic_details;
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
        return view('claim.edit',compact('claim'));
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
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'email_address' => 'required|max:120',
            'phone_number' => 'required|max:120',
            'insurance_company' => 'required|max:120',
            'insurance_type' => 'required|max:120',
            'policy_number' => 'required|max:120',
            'basic_details' => 'required|max:3000',
        ]);
        $claim->first_name =  $request->first_name;
        $claim->last_name =  $request->last_name;
        $claim->email_address =  $request->email_address;
        $claim->phone_number =  $request->phone_number;
        $claim->insurance_company =  $request->insurance_company;
        $claim->insurance_type =  $request->insurance_type;
        $claim->policy_number =  $request->policy_number;
        $claim->basic_details =  $request->basic_details;
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
