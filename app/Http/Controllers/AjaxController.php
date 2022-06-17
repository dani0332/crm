<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use Illuminate\Http\Request;
class AjaxController extends Controller
{
    
    public function carModelBasedOnCarMake(Request $request)
    {
        $make_code = $request->make_code;
        $carmodel =  CarModel::where('car_make_code', '=', $make_code)
        ->where('is_active', '=', 1)
        ->select('id', 'text', 'code')->get();
        return response()->json($carmodel);
    }

    public function carModelBasedOnCarMakeId(Request $request)
    {
        $make_id = $request->id;
        $carMakeCode =  CarMake::where('id', '=', $make_id)->value('code');
        if (!$carMakeCode) {
            $carMakeCode = $make_id;
        }
        $carmodel =  CarMake::where('code', '=', $carMakeCode)
        ->where('is_active', '=', 1)
        ->select('id', 'text', 'code')->get();
        return response()->json($carmodel);
    }
    public function getCarMake()
    {
        $carMakes = CarMake::where('is_active', '=', 1)
        ->select('id', 'text', 'code')->get();
        return response()->json($carMakes);
    }

    public function getCarModelDetails(Request $request)
    {
        $modelId = $request->car_model_id;
        
        $carModelDetail = CarModelDetail::select('cylinder', 'seating_capacity as seat_capacity', 'vehicle_type_id','text','id','is_default')
        ->where('car_model_id', '=', $modelId)
        ->get();
        if(!$carModelDetail) {
            $carModelDetail = CarModel::select('cylinder', 'seat_capacity', 'vehicle_type_id')
            ->where('id', '=', $modelId)
            ->get();
        }
        return response()->json($carModelDetail);
    }

    public function getCarModelTrimValues(Request $request) {
        $carModelDetail = CarModelDetail::select('cylinder', 'seating_capacity as seat_capacity', 'vehicle_type_id')
        ->where('id', '=', $request->id)
        ->first();
        return response()->json($carModelDetail);
    }
}
