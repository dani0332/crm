<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\InsuranceProvider;
use App\Models\VehicleDepreciation;
use Illuminate\Http\Request;

class VehicleDepreciationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:vehicle-depreciation-list|vehicle-depreciation-create|vehicle-depreciation-edit|vehicle-depreciation-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:vehicle-depreciation-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:vehicle-depreciation-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:vehicle-depreciation-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $data = VehicleDepreciation::select('vehicle_depreciation.*', 'ip.text as ip_text')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'vehicle_depreciation.insurance_provider_id')
            ->orderBy('created_at', 'desc')
            ->simplePaginate(10)->withQueryString();

        return inertia('VehicleDepreciation/Index', [
            'data' => $data,
        ]);
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
        $insuranceProviders = InsuranceProvider::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        return inertia('VehicleDepreciation/Form', [
            'carmakes' => $carmakes,
            'carmodels' => $carmodels,
            'insuranceProviders' => $insuranceProviders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
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
            'insurance_provider_value' => 'required',
        ]);
        if ($request->car_make_id || $request->car_model_id || $request->insurance_provider_value) {
            $existingDepreciation = VehicleDepreciation::where('car_make_id', $request->car_make_id)
                ->where('car_model_id', $request->car_model_id)
                ->where('insurance_provider_id', $request->insurance_provider_value)
                ->first();
            if ($existingDepreciation) {
                return redirect()->back()->with('message', 'Depreciation with same Make, Model, Insurer already exists.')->withInput($request->input());
            }
        }
        $depreciation = new VehicleDepreciation;
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
        if ($request->car_model_id) {
            $depreciation->car_model_id = $request->car_model_id;
        }
        if ($request->car_make_id) {
            $depreciation->car_make_id = $request->car_make_id;
        }
        if ($request->insurance_provider_value) {
            $depreciation->insurance_provider_id = $request->insurance_provider_value;
        }
        $depreciation->save();

        return redirect(route('vehicledepreciation.index'))->with('success', 'Vechile Depreciation has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function show(VehicleDepreciation $vehicledepreciation)
    {
        $data = VehicleDepreciation::find($vehicledepreciation->id);
        $carMake = CarMake::where('id', '=', $data->car_make_id)->first();
        $carModel = CarModel::where('id', '=', $data->car_model_id)->first();
        $insuranceProvider = InsuranceProvider::where('id', '=', $data->insurance_provider_id)->first();

        return inertia('VehicleDepreciation/Show', [
            'carmake' => $carMake,
            'carmodel' => $carModel,
            'insuranceProvider' => $insuranceProvider,
            'vehicledepreciation' => $data->toArray(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = VehicleDepreciation::find($id);
        $carMakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carModels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $insuranceProviders = InsuranceProvider::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        return inertia('VehicleDepreciation/Form', [
            'carmakes' => $carMakes,
            'carmodels' => $carModels,
            'insuranceProviders' => $insuranceProviders,
            'vehicledepreciation' => $data->toArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\VehicleDepreciation  $vehicleDepreciation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VehicleDepreciation $vehicledepreciation)
    {
        $this->validate($request, [
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
            'insurance_provider_value' => 'required',
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

        if ($request->car_model_id) {
            $vehicledepreciation->car_model_id = $request->car_model_id;
        }
        if ($request->car_make_id) {
            $vehicledepreciation->car_make_id = $request->car_make_id;
        }
        if ($request->insurance_provider_value) {
            $vehicledepreciation->insurance_provider_id = $request->insurance_provider_value;
        }

        $vehicledepreciation->save();

        return redirect(route('vehicledepreciation.show', $vehicledepreciation))->with('success', 'Vehicle Depreciation has been updated');
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

        return redirect(route('vehicledepreciation.index'))->with('message', 'Vehicle Depreciation has been deleted');
    }
}
