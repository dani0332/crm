<?php

namespace App\Http\Controllers;

use DataTables;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\InsuranceProvider;
use App\Models\VehicleValue;
use Illuminate\Http\Request;

class VehicleValueController extends Controller
{
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = VehicleValue::select('vehicle_value.*','car_make.text as car_make_text', 'ip.text as ip_text'
                ,'car_model.text as car_model_text','car_model_detail.text as car_trim_text')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'vehicle_value.insurance_provider_id')
            ->leftjoin('car_make','vehicle_value.car_make_id','car_make.id')
            ->leftjoin('car_model','vehicle_value.car_model_id','car_model.id')
            ->leftjoin('car_model_detail','vehicle_value.car_model_detail_id','car_model.id')
            ->orderBy('created_at','desc');
            if(isset($request->carmake) && !empty($request->carmake))
                $data->where('car_make_id', $request->carmake);
            if(isset($request->carmodel) && !empty($request->carmodel))
                $data->where('car_model_id', $request->carmodel);
            return DataTables::of($data)
                    ->addIndexColumn()
                    ->make(true);
        }
        return view('VehicleValue.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $carmakes = CarMake::where('is_active', '=', 1)->select('id', 'text', 'code')->orderBy('sort_order', 'asc')->get();
        $carmodels = CarModel::where('is_active', '=', 1)->select('id', 'text')->orderBy('sort_order', 'asc')->get();
        $insuranceProviders = InsuranceProvider::where('is_active', '=', 1)->select('id', 'text')->orderBy('sort_order', 'asc')->get();
        return view('VehicleValue.add',compact('carmakes', 'carmodels', 'insuranceProviders'));
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
            'lower_limit' => 'required|numeric|min:0',
            'upper_limit' => 'required|numeric|min:0',
        ]);
        if($request->car_make_value || $request->car_model_value || $request->insurance_provider_value)
        {
            $existingValue = VehicleValue::where('car_make_id', $request->car_make_value)
                                                        ->where('car_model_id', $request->car_model_value)
                                                        ->where('insurance_provider_id', $request->insurance_provider_value)
                                                        ->first();
            if($existingValue)
            {
                return redirect()->back()->with('message', 'Vechile Value with same Make, Model, Insurer already exists.')->withInput($request->input());
            }
        }
        $range = new VehicleValue();
        $range->lower_limit = $request->lower_limit;
        $range->upper_limit = $request->upper_limit;
        if( $request->car_model_id) $range->car_model_id =  $request->car_model_value;
        if( $request->car_make_value) $range->car_make_id =   $request->car_make_value;
        if( $request->insurance_provider_value) $range->insurance_provider_id =  $request->insurance_provider_value;
        $range->save();

        if(isset($request->return_to_view)) {
            return redirect("valuation/VehicleValue/".$range->id)->with('success', 'Vechile Value has been stored');
        }
        return redirect()->back()->with('success', 'Vechile Value has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(VehicleValue $VehicleValue)
    {
        $carMake = CarMake::where('id', '=', $VehicleValue->car_make_id)->select('id', 'text')->first();
        $carModel = CarModel::where('id', '=', $VehicleValue->car_model_id)->select('id', 'text')->first();
        $insuranceProvider = InsuranceProvider::where('id', '=', $VehicleValue->insurance_provider_id)->first();
        return view('VehicleValue.show',compact('VehicleValue', 'carMake', 'carModel', 'insuranceProvider'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(VehicleValue $VehicleValue)
    {
        $carMakes = CarMake::where('is_active', '=', 1)->select('id', 'text', 'code')->orderBy('sort_order', 'asc')->get();
        $carModels = CarModel::where('is_active', '=', 1)->select('id', 'text')->orderBy('sort_order', 'asc')->get();
        $insuranceProviders = InsuranceProvider::where('is_active', '=', 1)->select('id', 'text')->orderBy('sort_order', 'asc')->get();
        return view('VehicleValue.edit',compact('VehicleValue','carMakes','carModels', 'insuranceProviders'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VehicleValue $VehicleValue)
    {
        $this->validate($request,[
            'lower_limit' => 'required|numeric|min:0',
            'upper_limit' => 'required|numeric|min:0',
        ]);
        $VehicleValue->lower_limit = $request->lower_limit;
        $VehicleValue->upper_limit = $request->upper_limit;
       
        if( $request->car_model_id) $VehicleValue->car_model_id =  $request->car_model_value;
        if( $request->car_make_value) $VehicleValue->car_make_id =  $request->car_make_value;
        if( $request->insurance_provider_value) $VehicleValue->insurance_provider_id =  $request->insurance_provider_value;

        $VehicleValue->save();
        if(isset($request->return_to_view)) {
            return redirect("valuation/VehicleValue/".$VehicleValue->id)->with('success', 'Vehicle Value has been updated');
        }
        return redirect()->back()->with('success', 'Vehicle Value has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(VehicleValue $VehicleValue)
    {
        $VehicleValue->delete();
        return redirect()->route('VehicleValue.index')->with('message','Vehicle Value has been deleted');
    }
}
