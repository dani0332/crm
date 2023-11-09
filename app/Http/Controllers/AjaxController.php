<?php

namespace App\Http\Controllers;

use App\Enums\DocumentTypeCode;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Http\Requests\KycEntityDocRequest;
use App\Http\Requests\KycIndividualDocRequest;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\Entity;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Models\PersonalQuote;
use App\Services\HealthQuoteService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;

class AjaxController extends Controller
{
    use GenericQueriesAllLobs;

    protected $healthQuoteService;
    protected $quoteDocumentService;

    public function __construct(HealthQuoteService $healthQuoteService, QuoteDocumentService $quoteDocumentService)
    {
        $this->healthQuoteService = $healthQuoteService;
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
            ->select('id', 'text', 'code', 'car_make_code')->orderBy('text')->get();

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

    public function uploadKycIndividualDocument($quoteType, KycIndividualDocRequest $request)
    {
        try {
            $data = $request->validated();
            $data['nationality_text'] = Nationality::where('id', $data['nationality_id'])->value('text');
            $data['country_name'] = Nationality::where('id', $data['country_of_residence'])->value('country_name');
            $data['birth_place'] = Nationality::where('id', $data['place_of_birth'])->value('country_name');
            $data['document_type_code'] = DocumentTypeCode::KYCDOC;

            $pdf = PDF::loadView('pdf.kyc_individual_document', compact('data'));
            $pdf->setPaper('A4', 'landscape');
            $pdfFile = $pdf->output();

            $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);

            $document = $this->quoteDocumentService->uploadQuoteDocument($pdfFile, $data, $quote, true);

            if ($document) {
                return response()->json(['success' => true]);
            }
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }

        return response()->json(['error' => false]);
    }

    public function uploadKycEntityDocument($quoteType, KycEntityDocRequest $request)
    {
        try {
            $data = $request->validated();
            $data['industry_type_code'] = Entity::where('id', $data['industry_type'])->value('industry_type_code');
            $data['corporation_country'] = Nationality::where('id', $data['country_of_corporation'])->value('country_name');
            $data['manager_country'] = Nationality::where('id', $data['manager_nationality'])->value('text');
            $data['document_type_code'] = DocumentTypeCode::KYCDOC;

            $pdf = PDF::loadView('pdf.kyc_entity_document', compact('data'));
            $pdfFile = $pdf->output();
            info($quoteType);

            $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);

            $document = $this->quoteDocumentService->uploadQuoteDocument($pdfFile, $data, $quote, true);

            if ($document) {
                return response()->json(['success' => true]);
            }
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }

        return response()->json(['error' => false]);
    }
}
