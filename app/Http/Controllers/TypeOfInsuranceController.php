<?php

namespace App\Http\Controllers;

use App\Models\TypeOfInsurance;
use Illuminate\Http\Request;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;

class TypeOfInsuranceController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:type-of-insurance-list|type-of-insurance-create|type-of-insurance-edit|type-of-insurance-delete', ['only' => ['index','store']]);
         $this->middleware('permission:type-of-insurance-create', ['only' => ['create','store']]);
         $this->middleware('permission:type-of-insurance-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:type-of-insurance-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = TypeOfInsurance::select('*')->orderBy('sort_order','asc');
            return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        return view('typeofinsurance.actions', compact('row'))->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }

        return view('typeofinsurance.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('typeofinsurance.add');
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

        $typeofinsurance = new TypeOfInsurance();
        $typeofinsurance->text=  $request->text;
        $typeofinsurance->text_ar=  $request->text_ar;
        $typeofinsurance->is_active =  $request->is_active == 'on' ? 1 : 0;
        $typeofinsurance->sort_order =  $request->sort_order;
        $typeofinsurance->save();
        if(isset($request->return_to_view))
            return redirect("claim/typeofinsurance");
        return back()->with('success','Type Of Insurance has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TypeOfInsurance  $typeofinsurance
     * @return \Illuminate\Http\Response
     */
    public function show(TypeOfInsurance $typeofinsurance)
    {
        return view('typeofinsurance.show',compact('typeofinsurance'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TypeOfInsurance  $typeofinsurance
     * @return \Illuminate\Http\Response
     */
    public function edit(TypeOfInsurance $typeofinsurance)
    {
        return view('typeofinsurance.edit',compact('typeofinsurance'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TypeOfInsurance  $typeofinsurance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TypeOfInsurance $typeofinsurance)
    {
        $this->validate($request,[
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);
        $typeofinsurance->text=  $request->text;
        $typeofinsurance->text_ar=  $request->text_ar;
        $typeofinsurance->is_active =  $request->is_active == 'on' ? 1 : 0;
        $typeofinsurance->sort_order =  $request->sort_order;
        $typeofinsurance->save();
        if(isset($request->return_to_view))
            return redirect("claim/typeofinsurance");
        return back()->with('success','Type of Insurance has been Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TypeOfInsurance  $typeofinsurance
     * @return \Illuminate\Http\Response
     */
    public function destroy(TypeOfInsurance $typeofinsurance)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $typeofinsurance->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        return redirect()->route('typeofinsurance.index');
    }
}
