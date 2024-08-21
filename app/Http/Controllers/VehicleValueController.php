<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\InsuranceProvider;
use App\Models\VehicleValue;
use DataTables;
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
            $data = VehicleValue::select('vehicle_value.*', 'car_make.text as car_make_text', 'ip.text as ip_text', 'car_model.text as car_model_text', 'car_model_detail.text as car_trim_text')
                ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'vehicle_value.insurance_provider_id')
                ->leftjoin('car_make', 'vehicle_value.car_make_id', 'car_make.id')
                ->leftjoin('car_model', 'vehicle_value.car_model_id', 'car_model.id')
                ->leftjoin('car_model_detail', 'vehicle_value.car_model_detail_id', 'car_model_detail.id')
                ->orderBy('created_at', 'desc');
            if (isset($request->carmake) && ! empty($request->carmake)) {
                $data->where('car_make_id', $request->carmake);
            }
            if (isset($request->carmodel) && ! empty($request->carmodel)) {
                $data->where('car_model_id', $request->carmodel);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('vehiclevalue.view');
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

        return view('vehiclevalue.add', compact('carmakes', 'carmodels', 'insuranceProviders'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'current_value' => 'required|numeric|min:1',
            'insurance_provider_value' => 'required',
            'car_model_value' => 'required',
            'car_make_value' => 'required',
            'car_trim_value' => 'required',
        ]);

        $existingValue = VehicleValue::where('car_make_id', $request->car_make_value)
            ->where('car_model_id', $request->car_model_value)
            ->where('car_model_detail_id', $request->car_trim_value)
            ->where('insurance_provider_id', $request->insurance_provider_value)
            ->first();
        if ($existingValue) {
            return redirect()->back()->with('message', 'Vechile Value with same Make, Model, Insurer already exists.')->withInput($request->input());
        }
        $range = new VehicleValue;
        $range->current_value = $request->current_value;
        $range->car_model_id = $request->car_model_value;
        $range->car_make_id = $request->car_make_value;
        $range->car_model_detail_id = $request->car_trim_value;
        $range->insurance_provider_id = $request->insurance_provider_value;
        $range->save();

        if (isset($request->return_to_view)) {
            return redirect('valuation/vehiclevalue/'.$range->id)->with('success', 'Vechile Value has been stored');
        }

        return redirect()->back()->with('success', 'Vechile Value has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(VehicleValue $vehiclevalue)
    {
        $carMake = CarMake::where('id', '=', $vehiclevalue->car_make_id)->select('id', 'text')->first();
        $carModel = CarModel::where('id', '=', $vehiclevalue->car_model_id)->select('id', 'text')->first();
        $carModelDetail = CarModelDetail::where('id', '=', $vehiclevalue->car_model_detail_id)->select('id', 'text')->first();
        $insuranceProvider = InsuranceProvider::where('id', '=', $vehiclevalue->insurance_provider_id)->first();

        return view('vehiclevalue.show', compact('vehiclevalue', 'carMake', 'carModel', 'carModelDetail', 'insuranceProvider'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, VehicleValue $vehiclevalue)
    {
        $carMakes = CarMake::where('is_active', '=', 1)->select('id', 'text', 'code')->orderBy('sort_order', 'asc')->get();
        $selectedCarMake = CarMake::where('id', '=', $vehiclevalue->car_make_id)->select('id', 'text', 'code')->first();

        $carModels = CarModel::where('car_make_code', '=', $selectedCarMake->code)->where('is_active', '=', 1)->select('id', 'text')->orderBy('sort_order', 'asc')->get();

        $carModelDetails = CarModelDetail::where('car_model_id', '=', $vehiclevalue->car_model_id)->select('id', 'text')->get();
        $insuranceProviders = InsuranceProvider::where('is_active', '=', 1)->select('id', 'text')->orderBy('sort_order', 'asc')->get();

        return view('vehiclevalue.edit', compact('vehiclevalue', 'carMakes', 'carModels', 'carModelDetails', 'insuranceProviders'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VehicleValue $VehicleValue)
    {
        $this->validate($request, [
            'current_value' => 'required|numeric|min:1',
            'insurance_provider_value' => 'required',
            'car_model_value' => 'required',
            'car_make_value' => 'required',
            'car_trim_value' => 'required',
        ]);
        $VehicleValue->current_value = $request->current_value;
        $VehicleValue->car_model_id = $request->car_model_value;
        $VehicleValue->car_make_id = $request->car_make_value;
        $VehicleValue->car_model_detail_id = $request->car_trim_value;
        $VehicleValue->insurance_provider_id = $request->insurance_provider_value;
        $VehicleValue->save();
        if (isset($request->return_to_view)) {
            return redirect('valuation/vehiclevalue/'.$VehicleValue->id)->with('success', 'Vehicle Value has been updated');
        }

        return redirect()->back()->with('success', 'Vehicle Value has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, VehicleValue $VehicleValue)
    {
        $vehiclevalue = VehicleValue::find($id);
        $vehiclevalue->delete();

        return redirect()->route('vehiclevalue.index')->with('message', 'Vehicle Value has been deleted');
    }
}
