<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\InsuranceProvider;
use App\Models\VehicleRange;
use DataTables;
use Illuminate\Http\Request;

class VehicleRangeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = VehicleRange::select('vehicle_valuation_range.*', 'car_make.text as car_make_text', 'ip.text as ip_text', 'car_model.text as car_model_text')
                ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'vehicle_valuation_range.insurance_provider_id')
                ->leftjoin('car_make', 'vehicle_valuation_range.car_make_id', 'car_make.id')
                ->leftjoin('car_model', 'vehicle_valuation_range.car_model_id', 'car_model.id')
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

        return view('vehiclerange.view');
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

        return view('vehiclerange.add', compact('carmakes', 'carmodels', 'insuranceProviders'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'lower_limit' => 'required|numeric|min:0',
            'upper_limit' => 'required|numeric|min:0',
            'insurance_provider_value' => 'required',
        ]);
        if ($request->car_make_value || $request->car_model_value || $request->insurance_provider_value) {
            $existingRange = VehicleRange::where('car_make_id', $request->car_make_value)
                ->where('car_model_id', $request->car_model_value)
                ->where('insurance_provider_id', $request->insurance_provider_value)
                ->first();
            if ($existingRange) {
                return redirect()->back()->with('message', 'Range with same Make, Model, Insurer already exists.')->withInput($request->input());
            }
        }
        $range = new VehicleRange;
        $range->lower_limit = $request->lower_limit;
        $range->upper_limit = $request->upper_limit;
        if ($request->car_model_id) {
            $range->car_model_id = $request->car_model_value;
        }
        if ($request->car_make_value) {
            $range->car_make_id = $request->car_make_value;
        }
        if ($request->insurance_provider_value) {
            $range->insurance_provider_id = $request->insurance_provider_value;
        }
        $range->save();

        if (isset($request->return_to_view)) {
            return redirect('valuation/vehiclerange/'.$range->id)->with('success', 'Vechile Range has been stored');
        }

        return redirect()->back()->with('success', 'Vechile Range has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(VehicleRange $vehiclerange)
    {
        $carMake = CarMake::where('id', '=', $vehiclerange->car_make_id)->select('id', 'text')->first();
        $carModel = CarModel::where('id', '=', $vehiclerange->car_model_id)->select('id', 'text')->first();
        $insuranceProvider = InsuranceProvider::where('id', '=', $vehiclerange->insurance_provider_id)->first();

        return view('vehiclerange.show', compact('vehiclerange', 'carMake', 'carModel', 'insuranceProvider'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(VehicleRange $vehiclerange)
    {
        $carMakes = CarMake::where('is_active', '=', 1)->select('id', 'text', 'code')->orderBy('sort_order', 'asc')->get();
        $carModels = CarModel::where('is_active', '=', 1)->select('id', 'text')->orderBy('sort_order', 'asc')->get();
        $insuranceProviders = InsuranceProvider::where('is_active', '=', 1)->select('id', 'text')->orderBy('sort_order', 'asc')->get();

        return view('vehiclerange.edit', compact('vehiclerange', 'carMakes', 'carModels', 'insuranceProviders'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VehicleRange $vehiclerange)
    {
        $this->validate($request, [
            'lower_limit' => 'required|numeric|min:0',
            'upper_limit' => 'required|numeric|min:0',
            'insurance_provider_value' => 'required',
        ]);
        $vehiclerange->lower_limit = $request->lower_limit;
        $vehiclerange->upper_limit = $request->upper_limit;

        if ($request->car_model_id) {
            $vehiclerange->car_model_id = $request->car_model_value;
        }
        if ($request->car_make_value) {
            $vehiclerange->car_make_id = $request->car_make_value;
        }
        if ($request->insurance_provider_value) {
            $vehiclerange->insurance_provider_id = $request->insurance_provider_value;
        }

        $vehiclerange->save();
        if (isset($request->return_to_view)) {
            return redirect('valuation/vehiclerange/'.$vehiclerange->id)->with('success', 'Vehicle Range has been updated');
        }

        return redirect()->back()->with('success', 'Vehicle Range has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(VehicleRange $vehiclerange)
    {
        $vehiclerange->delete();

        return redirect()->route('vehiclerange.index')->with('message', 'Vehicle Range has been deleted');
    }
}
