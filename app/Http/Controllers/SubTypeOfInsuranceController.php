<?php

namespace App\Http\Controllers;

use App\Models\SubTypeOfInsurance;
use Illuminate\Http\Request;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;

class SubTypeOfInsuranceController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:sub-type-of-insurance-list|sub-type-of-insurance-create|sub-type-of-insurance-edit|sub-type-of-insurance-delete', ['only' => ['index','store']]);
         $this->middleware('permission:sub-type-of-insurance-create', ['only' => ['create','store']]);
         $this->middleware('permission:sub-type-of-insurance-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:sub-type-of-insurance-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = SubTypeOfInsurance::select('*');
            return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        return view('subtypeofinsurance.actions', compact('row'))->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        
        return view('subtypeofinsurance.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('subtypeofinsurance.add');
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
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);

        $subtypeofinsurance = new SubTypeOfInsurance();
        $subtypeofinsurance->text=  $request->text;
        $subtypeofinsurance->text_ar=  $request->text_ar;
        $subtypeofinsurance->is_active =  $request->is_active == 'on' ? 1 : 0;
        $subtypeofinsurance->sort_order =  $request->sort_order;
        $subtypeofinsurance->save();
        return back()
            ->with('success','Sub Type Of Insurance has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SubTypeOfInsurance  $subTypeOfInsurance
     * @return \Illuminate\Http\Response
     */
    public function show(SubTypeOfInsurance $subtypeofinsurance)
    {
        return view('subtypeofinsurance.show',compact('subtypeofinsurance'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SubTypeOfInsurance  $subTypeOfInsurance
     * @return \Illuminate\Http\Response
     */
    public function edit(SubTypeOfInsurance $subtypeofinsurance)
    {
        return view('subtypeofinsurance.edit',compact('subtypeofinsurance'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SubTypeOfInsurance  $subTypeOfInsurance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SubTypeOfInsurance $subtypeofinsurance)
    {
        $this->validate($request,[
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);
        $subtypeofinsurance->text=  $request->text;
        $subtypeofinsurance->text_ar=  $request->text_ar;
        $subtypeofinsurance->is_active =  $request->is_active == 'on' ? 1 : 0; 
        $subtypeofinsurance->sort_order =  $request->sort_order;    
        $subtypeofinsurance->save();
        return back()
            ->with('success','Sub Type of Insurance has been Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SubTypeOfInsurance  $subTypeOfInsurance
     * @return \Illuminate\Http\Response
     */
    public function destroy(SubTypeOfInsurance $subtypeofinsurance)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $subtypeofinsurance->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        return redirect()->route('subtypeofinsurance.index');
    }
}
