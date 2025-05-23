<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Models\PersonalQuote;
use App\Models\RenewalBatch;
use App\Services\CRUDService;
use App\Services\QuoteDocumentService;
use App\Traits\CentralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    use CentralTrait;

    protected $quoteDocumentService;

    public function __construct(QuoteDocumentService $quoteDocumentService)
    {
        $this->quoteDocumentService = $quoteDocumentService;
    }

    public function carModelBasedOnCarMake(Request $request)
    {
        $carmodel = CarModel::activeWithCode($request->make_code)
            ->select('id', 'text', 'code')->orderBy('text')->get();

        return response()->json($carmodel);
    }

    public function carModelBasedOnCarMakeId(Request $request)
    {
        $carMakeCode = CarMake::activeWithId($request->id)->value('code');
        if (! $carMakeCode) {
            $carMakeCode = $request->id;
        }
        $carmodel = CarModel::activeWithCode($carMakeCode)
            ->select('id', 'text', 'code', 'car_make_code', 'is_commercial')->orderBy('text')->get();

        return response()->json($carmodel);
    }

    public function getCarMake()
    {
        $carMakes = CarMake::active()->select('id', 'text', 'code')->orderBy('text')->get();

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
        $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
        if (! $quoteModel) {
            return response()->json(['success' => false]);
        }
        $payment = Payment::where('code', $request->code)->first();
        if (! $payment) {
            return response()->json(['success' => false]);
        }

        $payment->payment_status_id = PaymentStatusEnum::PAID;
        $payment->captured_at = now();
        $payment->save();
        $paymentLog = new PaymentStatusLog([
            'previous_payment_status_id' => $payment->paymentStatusLogs->last()->current_payment_status_id,
            'current_payment_status_id' => PaymentStatusEnum::PAID,
            'payment_code' => $request->code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentLog->save();
        $quoteModel->quote_status_id = QuoteStatusEnum::TransactionApproved;
        $quoteModel->save();

        app(CRUDService::class)->calculateScore($quoteModel, $request->modelType);

        return response()->json(['success' => true]);
    }

    public function generatePaymentLink(Request $request)
    {
        $payment = Payment::where('code', '=', $request->paymentCode)->first();
        if (! $payment) {
            return response()->json(['success' => false]);
        }
        if ($payment->payment_link != null && now() < Carbon::parse($payment->payment_link_created_at)->addDays(3)) {
            return response()->json(['success' => true, 'payment_link' => $payment->payment_link]);
        } else {
            $quoteModel = $this->getQuoteObject($request->modelType, $request->quoteId);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->modelType));

            $description = (get_class($quoteModel) == PersonalQuote::class) ? ($payment->personalPlan->text ?? '') : ($quoteModel->plan->text ?? '');

            $paymentLink = config('constants.PAYMENT_REDIRECT_LINK');

            $paymentLink = $payment->payment_methods_code == PaymentMethodsEnum::InsureNowPayLater ? $paymentLink.'tabby' : $paymentLink.'checkout';

            $paymentParams = [
                'code' => $payment->code,
                'quoteTypeId' => $quoteTypeId,
            ];
            $paymentLinkURL = $paymentLink.'?'.http_build_query($paymentParams);

            $invoiceRequestData = [
                'firstName' => $quoteModel->first_name,
                'lastName' => $quoteModel->last_name,
                'email' => $quoteModel->email,
                'emailSubject' => 'Payment Request',
                'items' => [
                    [
                        'description' => $description,
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

            info('Request object for '.$quoteModel->uuid.' is '.json_encode($invoiceRequestData));

            return response()->json(['success' => true, 'payment_link' => $paymentLinkURL]);
        }
    }

    public function commercialCarModelBasedOnCarMakeId(Request $request)
    {
        $carMakeCode = $request->get('make_code');

        if ($carMakeCode) {
            $carModel = CarModel::where('car_make_code', $carMakeCode)
                ->select('id', 'text', 'code')
                ->where('is_commercial', true)
                ->where('is_active', true)
                ->orderBy('text')
                ->get();

            return response()->json($carModel);
        } else {
            return response()->json([]);
        }
    }

    public function updateRisk($quoteType, Request $request)
    {
        $request->validate([
            'quote_uuid' => 'required',
        ]);
        $quote = $this->getQuoteObjectBy($quoteType, $request->quote_uuid, 'uuid');
        $detail = $this->getQuoteDetailObject($quoteType, $quote->id);
        $detail->risk_score_override = $request->risk_override;
        $detail->risk_score_override_date = Carbon::now();
        $detail->risk_score_override_by = auth()->user()->id;
        $detail->save();

        app(CRUDService::class)->calculateScore($quote, $quoteType);
    }

    public function quoteDetail($quoteType, $id)
    {
        if ($quoteType && $id) {
            $quote = $this->getQuoteObjectBy($quoteType, $id, 'uuid');

            $detail = $this->getQuoteDetailObject($quoteType, $quote->id);

            return $detail;
        } else {
            return response()->json(['success' => false]);
        }
    }

    public function bikeModelBasedOnCarMakeId(Request $request)
    {
        $carMakeCode = CarMake::activeWithId($request->id)->value('code');
        if (! $carMakeCode) {
            $carMakeCode = $request->id;
        }
        $carmodel = CarModel::activeWithCode($carMakeCode)
            ->select('id', 'text', 'code', 'car_make_code')
            ->where('quote_type_id', QuoteTypeId::Bike)
            ->orderBy('text')
            ->get();

        return response()->json($carmodel);
    }
    public function getBikeModelDetails(Request $request)
    {
        $bikeModelDetail = CarModelDetail::active()
            ->select('cubic_capacity', 'seating_capacity as seat_capacity')
            ->where('car_model_id', $request->bike_model_id)
            ->get();

        return response()->json($bikeModelDetail);
    }

    public function getBatchNamesByQuoteTypeId(Request $request)
    {
        $renewalBatch = RenewalBatch::orderBy('name')->select(['name as text']);
        if (isset($request->quote_type_id) && $request->quote_type_id == QuoteTypeId::Car) {
            /** Motor Selected */
            $renewalBatch->where('quote_type_id', QuoteTypeId::Car);
        } elseif (isset($request->quote_type_id) && $request->quote_type_id != QuoteTypeId::Car) {
            /** Non-Motor Selected */
            $renewalBatch->where('quote_type_id', '<>', QuoteTypeId::Car)->orWhereNull('quote_type_id');
        }

        return response()->json($renewalBatch->groupBy('name')->get()); // laravel automatically converts to JSON
    }
}
