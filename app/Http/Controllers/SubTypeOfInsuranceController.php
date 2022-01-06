<?php

namespace App\Http\Controllers;

use App\Models\SubTypeOfInsurance;
use Illuminate\Http\Request;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;
use App\Http\Requests\SubTypeInsuranceRequest;
use App\Http\Resources\SubTypeInsuranceResource;
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
            $data = SubTypeOfInsurance::select('*')->orderBy('sort_order','asc');
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
    public function store(SubTypeInsuranceRequest $request, SubTypeOfInsurance $subtypeofinsurance)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $id = $subtypeofinsurance->create($validated)->id;
        if(isset($request->return_to_view)) {
            return redirect("claim/subtypeofinsurance/".$id)->with('success', 'Sub Type Of Insurance has been stored');
        }
        return redirect()->back()->with('success', 'Sub Type Of Insurance has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SubTypeOfInsurance  $subTypeOfInsurance
     * @return \Illuminate\Http\Response
     */
    public function show(SubTypeOfInsurance $subtypeofinsurance)
    {
        $subtypeofinsurance = new SubTypeInsuranceResource($subtypeofinsurance);
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
        $subtypeofinsurance = new SubTypeInsuranceResource($subtypeofinsurance);
        return view('subtypeofinsurance.edit',compact('subtypeofinsurance'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SubTypeOfInsurance  $subTypeOfInsurance
     * @return \Illuminate\Http\Response
     */
    public function update(SubTypeInsuranceRequest $request, SubTypeOfInsurance $subtypeofinsurance)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $subtypeofinsurance->update($validated);
        if(isset($request->return_to_view)) {
            return redirect("claim/subtypeofinsurance/".$subtypeofinsurance->id)->with('success', 'Sub Type Of Insurance has been updated');
        }
        return redirect()->back()->with('success', 'Sub Type Of Insurance has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SubTypeOfInsurance  $subTypeOfInsurance
     * @return \Illuminate\Http\Response
     */
    public function destroy(SubTypeOfInsurance $subtypeofinsurance)
    {
        $subtypeofinsurance->delete();
        return redirect()->route('subtypeofinsurance.index')->with('message','Sub Type of Insurance has been deleted');
    }
}
