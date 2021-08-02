<?php

namespace App\Http\Controllers;
use DataTables;
use App\Models\VehicleDepreciation;
use App\Models\CarMake;
use App\Models\ClaimsStatus;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use DB;

class VehicleDepreciationController extends Controller
{
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
         $this->middleware('permission:vehicle-depreciation-list|vehicle-depreciation-create|vehicle-depreciation-edit|vehicle-depreciation-delete', ['only' => ['index','store']]);
         $this->middleware('permission:vehicle-depreciation-create', ['only' => ['create','store']]);
         $this->middleware('permission:vehicle-depreciation-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:vehicle-depreciation-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        if ($request->ajax()) {

            $data = VehicleDepreciation::select('vehicle_depreciation.*','car_make.text as car_make_text'
            ,'car_model.text as car_model_text')
            ->leftjoin('car_make','vehicle_depreciation.car_make_id','car_make.id')
            ->leftjoin('car_model','vehicle_depreciation.car_model_id','car_model.id')
            ->orderBy('created_at','desc');
            if(isset($request->carmake) && !empty($request->carmake))
                $data->where('car_make_id', $request->carmake);
            if(isset($request->carmodel) && !empty($request->carmodel))
                $data->where('car_model_id', $request->carmodel);
            return DataTables::of($data)
                    ->addIndexColumn()
                    ->make(true);
        }
        return view('vehicledepreciation.view');
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function show(VehicleDepreciation $vehicleDepreciation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function edit(VehicleDepreciation $vehicleDepreciation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VehicleDepreciation $vehicleDepreciation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function destroy(VehicleDepreciation $vehicleDepreciation)
    {
        //
    }
}
