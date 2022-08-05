<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatusEnum;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\Payment;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function carModelBasedOnCarMake(Request $request)
    {
        $carmodel = CarModel::activeWithCode($request->make_code)
        ->select('id', 'text', 'code')->get();

        return response()->json($carmodel);
    }

    public function carModelBasedOnCarMakeId(Request $request)
    {
        $carMakeCode = CarMake::activeWithId($request->id)->value('code');
        if (! $carMakeCode) {
            $carMakeCode = $request->id;
        }
        $carmodel = CarModel::activeWithCode($carMakeCode)
        ->select('id', 'text', 'code', 'car_make_code')->get();

        return response()->json($carmodel);
    }

    public function getCarMake()
    {
        $carMakes = CarMake::active()->select('id', 'text', 'code')->get();

        return response()->json($carMakes);
    }

    public function getCarModelDetails(Request $request)
    {
        $carModelDetail = CarModelDetail::active()
        ->select('cylinder', 'seating_capacity as seat_capacity', 'vehicle_type_id', 'text', 'id', 'is_default')
        ->where('car_model_id', $request->car_model_id)
        ->get();
        if (! $carModelDetail) {
            $carModelDetail = CarModel::active()
            ->select('cylinder', 'seat_capacity', 'vehicle_type_id')
            ->whereId($request->car_model_id)
            ->get();
        }

        return response()->json($carModelDetail);
    }

    public function getCarModelTrimValues(Request $request)
    {
        $carModelDetail = CarModelDetail::active()
        ->select('cylinder', 'seating_capacity as seat_capacity', 'vehicle_type_id')
        ->whereId($request->id)
        ->first();

        return response()->json($carModelDetail);
    }

    public function updatePaymentStatus(Request $request)
    {
        $payment = Payment::where('code', $request->code)->first();
        $payment->payment_status_id = PaymentStatusEnum::PAID;
        $payment->save();
        return response()->json(['success' => true]);
    }
}
