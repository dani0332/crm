<?php

namespace App\Http\Controllers;
use DataTables;
use App\Models\VehicleDepreciation;
use App\Models\CarMake;
use App\Models\CarModel;
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
        $carmakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carmodels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        return view('vehicledepreciation.add',compact('carmakes', 'carmodels'));
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
            'car_make_value' => 'required',
            'car_model_value' => 'required',
            'first_year' => 'required|numeric|min:1',
            'second_year' => 'required|numeric|min:1',
            'third_year' => 'required|numeric|min:1',
            'fourth_year' => 'required|numeric|min:1',
            'fifth_year' => 'required|numeric|min:1',
            'sixth_year' => 'required|numeric|min:1',
            'seventh_year' => 'required|numeric|min:1',
            'eighth_year' => 'required|numeric|min:1',
            'ninth_year' => 'required|numeric|min:1',
            'tenth_year' => 'required|numeric|min:1',
            'upper_limit' => 'required|numeric|min:1',
            'lower_limit' => 'required|numeric|min:1',
        ]);

        $depreciation = new VehicleDepreciation();
        $depreciation->first_year = $request->first_year;
        $depreciation->second_year = $request->second_year;
        $depreciation->third_year = $request->third_year;
        $depreciation->fourth_year = $request->fourth_year;
        $depreciation->fifth_year = $request->fifth_year;
        $depreciation->sixth_year = $request->sixth_year;
        $depreciation->seventh_year = $request->seventh_year;
        $depreciation->eighth_year = $request->eighth_year;
        $depreciation->ninth_year = $request->ninth_year;
        $depreciation->tenth_year = $request->tenth_year;
        $depreciation->upper_limit = $request->upper_limit;
        $depreciation->lower_limit = $request->lower_limit;
        $depreciation->car_model_id =  $request->car_model_value;
        $depreciation->car_make_id =  $request->car_make_value;
        $depreciation->save();

        if(isset($request->return_to_view)) {
            return redirect("valuation/vehicledepreciation/".$depreciation->id)->with('success', 'Vechile Depreciation has been stored');
        }
        return redirect()->back()->with('success', 'Vechile Depreciation has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function show(VehicleDepreciation $vehicledepreciation)
    {
        $carMake = CarMake::where('id', '=', $vehicledepreciation->car_make_id)->skip(0)->take(1)->get();
        $carModel = CarModel::where('id', '=', $vehicledepreciation->car_model_id)->skip(0)->take(1)->get();
        return view('vehicledepreciation.show',compact('vehicledepreciation', 'carMake', 'carModel'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function edit(VehicleDepreciation $vehicledepreciation)
    {
        $carmakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carMake = CarMake::where('id', '=', $vehicledepreciation->car_make_id)->skip(0)->take(1)->get();   
        $carmodels = CarModel::where('car_make_code', '=', $carMake->first()->code)->get();
        return view('vehicledepreciation.edit',compact('vehicledepreciation','carmakes','carmodels'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VehicleDepreciation $vehicledepreciation)
    {
        $this->validate($request,[
            'car_make_value' => 'required',
            'car_model_value' => 'required',
            'first_year' => 'required|numeric|min:1',
            'second_year' => 'required|numeric|min:1',
            'third_year' => 'required|numeric|min:1',
            'fourth_year' => 'required|numeric|min:1',
            'fifth_year' => 'required|numeric|min:1',
            'sixth_year' => 'required|numeric|min:1',
            'seventh_year' => 'required|numeric|min:1',
            'eighth_year' => 'required|numeric|min:1',
            'ninth_year' => 'required|numeric|min:1',
            'tenth_year' => 'required|numeric|min:1',
            'upper_limit' => 'required|numeric|min:1',
            'lower_limit' => 'required|numeric|min:1',
        ]);
        $vehicledepreciation->first_year = $request->first_year;
        $vehicledepreciation->second_year = $request->second_year;
        $vehicledepreciation->third_year = $request->third_year;
        $vehicledepreciation->fourth_year = $request->fourth_year;
        $vehicledepreciation->fifth_year = $request->fifth_year;
        $vehicledepreciation->sixth_year = $request->sixth_year;
        $vehicledepreciation->seventh_year = $request->seventh_year;
        $vehicledepreciation->eighth_year = $request->eighth_year;
        $vehicledepreciation->ninth_year = $request->ninth_year;
        $vehicledepreciation->tenth_year = $request->tenth_year;
        $vehicledepreciation->upper_limit = $request->upper_limit;
        $vehicledepreciation->lower_limit = $request->lower_limit;
        $vehicledepreciation->car_model_id =  $request->car_model_value;
        $vehicledepreciation->car_make_id =  $request->car_make_value;
        $vehicledepreciation->save();
        if(isset($request->return_to_view)) {
            return redirect("valuation/vehicledepreciation/".$vehicledepreciation->id)->with('success', 'Vehicle Depreciation has been updated');
        }
        return redirect()->back()->with('success', 'Vehicle Depreciation has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function destroy(VehicleDepreciation $vehicledepreciation)
    {
        $vehicledepreciation->delete();
        return redirect()->route('vehicledepreciation.index')->with('message','Vehicle Depreciation has been deleted');
    }
}
