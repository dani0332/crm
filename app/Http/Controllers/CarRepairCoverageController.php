<?php

namespace App\Http\Controllers;

use App\Models\CarRepairCoverage;
use Illuminate\Http\Request;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;

class CarRepairCoverageController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:car-repair-coverage-list|car-repair-coverage-create|car-repair-coverage-edit|car-repair-coverage-delete', ['only' => ['index','store']]);
         $this->middleware('permission:car-repair-coverage-create', ['only' => ['create','store']]);
         $this->middleware('permission:car-repair-coverage-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:car-repair-coverage-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CarRepairCoverage::select('*');
            return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        return view('carrepaircoverage.actions', compact('row'))->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        
        return view('carrepaircoverage.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('carrepaircoverage.add');
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

        $carrepaircoverage = new CarRepairCoverage();
        $carrepaircoverage->text=  $request->text;
        $carrepaircoverage->text_ar=  $request->text_ar;
        $carrepaircoverage->is_active =  $request->is_active == 'on' ? 1 : 0;
        $carrepaircoverage->sort_order =  $request->sort_order;
        $carrepaircoverage->save();
        return back()
            ->with('success','Car Repair Coverage has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CarRepairCoverage  $carRepairCoverage
     * @return \Illuminate\Http\Response
     */
    public function show(CarRepairCoverage $carrepaircoverage)
    {
        return view('carrepaircoverage.show',compact('carrepaircoverage'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CarRepairCoverage  $carRepairCoverage
     * @return \Illuminate\Http\Response
     */
    public function edit(CarRepairCoverage $carrepaircoverage)
    {
        return view('carrepaircoverage.edit',compact('carrepaircoverage'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CarRepairCoverage  $carRepairCoverage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CarRepairCoverage $carrepaircoverage)
    {
        $this->validate($request,[
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);
        $carrepaircoverage->text=  $request->text;
        $carrepaircoverage->text_ar=  $request->text_ar;
        $carrepaircoverage->is_active =  $request->is_active == 'on' ? 1 : 0; 
        $carrepaircoverage->sort_order =  $request->sort_order;    
        $carrepaircoverage->save();
        return back()
            ->with('success','Car Repair Coverage has been Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CarRepairCoverage  $carRepairCoverage
     * @return \Illuminate\Http\Response
     */
    public function destroy(CarRepairCoverage $carrepaircoverage)
    {
        $carrepaircoverage->delete();
        return back()
            ->with('success','Car Repair Coverage has been Deleted');
    }
}
