<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatusEnum;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Services\NetworkPaymentService;
use Carbon\Carbon;
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
        $paymentLog = new PaymentStatusLog([
            'previous_payment_status_id' => $payment->paymentStatusLogs->last()->current_payment_status_id,
            'current_payment_status_id' => PaymentStatusEnum::PAID,
            'payment_code' => $request->code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentLog->save();

        return response()->json(['success' => true]);
    }

    public function generatePaymentLink(Request $request)
    {
        $payment = Payment::where('code', '=', $request->paymentCode)->first();
        if ($payment->payment_link != null && now() < Carbon::parse($payment->payment_link_created_at)->addDays(3)) {
            return response()->json(['success' => true, 'payment_link' => $payment->payment_link]);
        } else {
            $model = '\\App\\Models\\'.ucwords($request->modelType).'Quote';
            $quoteModel = $model::where('id', $request->quoteId)->first();

            $tokenRequest = NetworkPaymentService::sendNetworkTokenRequest();
            if ($tokenRequest->getStatusCode() == 200) {
                $getContents = $tokenRequest->getBody();
                $decodedContent = json_decode($getContents);
                $token = $decodedContent->access_token;
                $invoiceRequestData = [
                    'firstName' => $quoteModel->first_name,
                    'lastName' => $quoteModel->last_name,
                    'email' => $quoteModel->email,
                    'emailSubject' => 'Payment Request',
                    'invoiceExpiryDate' => now()->addDays(3)->format('Y-m-d'),
                    'transactionType' => 'AUTH',
                    'paymentAttempts' => 3,
                    'items' => [
                        [
                            'description' => $quoteModel->plan->text,
                            'totalPrice' => [
                                'currencyCode' => 'AED',
                                'value' => ceil($payment->captured_amount * 100),
                            ],
                            'quantity' => 1,
                        ],
                    ],
                    'total' => [
                        'currencyCode' => 'AED',
                        'value' => ceil($payment->captured_amount * 100),
                    ],
                    'merchantOrderReference' => strtoupper($payment->code),
                ];
                $invoiceRequest = NetworkPaymentService::sendNetworkInvoiceRequest($invoiceRequestData, $token);
                if ($invoiceRequest->getStatusCode() == 201) {
                    $invoiceResponse = $invoiceRequest->getBody();
                    $parsedInvoiceResponse = json_decode($invoiceResponse);
                    $paymentLink = $parsedInvoiceResponse->_links->payment->href;
                    $payment->payment_link = $paymentLink;
                    $payment->payment_link_created_at = now();
                    $payment->save();

                    return response()->json(['success' => true, 'payment_link' => $paymentLink]);
                } else {
                    return response()->json(['success' => false, 'message' => 'Something went wrong', 'exception' => $invoiceRequest->getBody(), 'status_code' => $invoiceRequest->getStatusCode()]);
                }
            } else {
                return 'API failed';
            }
        }
    }
}
