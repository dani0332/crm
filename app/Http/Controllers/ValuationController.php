<?php

namespace App\Http\Controllers;

use App\Facades\Ken;
use App\Services\DropdownSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValuationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:vehicle-valuation-list', ['only' => ['index']]);
    }

    public function index()
    {
        $carMakes = DB::table('car_make')->where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        $dropdownSourceList = ['bike_make_id', 'bike_model_id'];
        $dropdownSource = [];
        foreach ($dropdownSourceList as $value) {
            $data = (new DropdownSourceService)->getDropdownSource($value);
            $dropdownSource[$value] = $data;
        }

        return inertia('Valuation/Car', [
            'carMakes' => $carMakes,
            'dropdownSource' => $dropdownSource,
        ]);
    }

    public function calculateValuation(Request $request)
    {
        if (! isset($request->carModelDetailId) && ! isset($request->yearOfManufacture)) {
            return response()->json(['error' => 'Please select car make, model and year of manufacture'], 422);
        }

        return Ken::request('/get-vehicle-value', 'post', ['carModelDetailId' => $request->carModelDetailId, 'yearOfManufacture' => $request->yearOfManufacture]);
    }

    public function carModelBasedOnCarMake(Request $request)
    {
        $make_code = $request->make_code;
        $carmodel = DB::table('car_model')->where('car_make_code', '=', $make_code)->where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get(['id', 'text', 'code']);

        return response()->json($carmodel);
    }

    public function carTrimBasedOnCarModel(Request $request)
    {
        $modelId = $request->modelId;
        $carModelDetails = DB::table('car_model_detail')->where('car_model_id', '=', $modelId)->where('is_active', '=', 1)->orderBy('text', 'asc')->get(['id', 'text']);

        return response()->json($carModelDetails);
    }
}
