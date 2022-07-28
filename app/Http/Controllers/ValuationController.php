<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class ValuationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:vehicle-valuation-list', ['only' => ['index', 'store']]);
    }

    public function calculateValuation(Request $request)
    {
        $carMakes = DB::table('car_make')->where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        return view('valuation.view', compact('carMakes'));
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
