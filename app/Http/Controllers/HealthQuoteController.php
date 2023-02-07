<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Models\HealthQuote;
use App\Services\HealthQuoteService;
use Config;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HealthQuoteController extends Controller
{
    protected $healthQuoteService;

    /**
     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response
     */
    public function __construct(HealthQuoteService $healthQuoteService)
    {
        $this->middleware('permission:health-quotes-list|health-quotes-resubmit-api', ['only' => ['index', 'store']]);
        $this->healthQuoteService = $healthQuoteService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            Log::info('Inside HealthQuote Index');
            $data = HealthQuote::select('*')->orderBy('created_at', 'desc');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('healthquotes.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('healthquotes.view');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\HealthQuote  $healthquote
     * @return \Illuminate\Http\Response
     */
    public function show(HealthQuote $healthquote)
    {
        return view('healthquotes.show', compact('healthquote'));
    }

    public function resubmitApi(Request $request)
    {
        $HealthQuotes = $request->car_quotes;
        if (count($HealthQuotes) > 0) {
            foreach ($HealthQuotes as $HealthQuote) {
                $HealthQuoteModel = HealthQuote::find($HealthQuote);
                $this->sendDataCentralizedApi($HealthQuoteModel);
            }
        }

        return true;
    }

    public function sendDataCentralizedApi($bean)
    {
        $refUrl = $bean->reference_url;
        $carQuoteSaveApi = '/api/v1-wrapper-save-car-quote';
        $emailSys = Config::get('constants.emailL_sys');
        $centralApi = Config::get('constants.central_api_endpoint').$carQuoteSaveApi;
        $centralApiToken = Config::get('constants.central_api_token');

        $carValue = $bean->car_value == '?' ? '0' : $bean->car_value;

        $uaeLicenseHeldFor = $bean->uae_license_held_for == '' ? null : $bean->uae_license_held_for_id;

        $emirateOfRegistration = $bean->emirate_of_registration == '' ? null : $bean->emirate_of_registration_id;

        $claimsHistory = $bean->claims_history == '' ? null : $bean->claim_history_id;

        $typeOfCarIns = $bean->type_of_car_insurance_c == '' ? null : $bean->car_type_insurance_id;

        $nationality = \App\Models\Nationality::find($bean->nationality_id);
        $nationality = $nationality ? $nationality->code : '';

        $carMakeCode = \App\Models\CarMake::find($bean->car_make_id);
        $carMakeCode = $carMakeCode ? $carMakeCode->code : '';

        $carModelCode = \App\Models\CarModel::find($bean->car_model_id);
        $carModelCode = $carModelCode ? $carModelCode->code : '';

        $data = [
            'firstName' => $bean->first_name,
            'lastName' => $bean->last_name,
            'email' => $bean->email,
            'mobileNo' => $bean->mobile_no,
            'nationality' => $nationality,
            'carValue' => $carValue,
            'currentlyInsuredWith' => $bean->currently_insured_with,
            'carMakeCode' => $carMakeCode,
            'carModelCode' => $carModelCode,
            'yearOfManufacture' => $bean->year_of_manufacture,
            'source' => $bean->source,
            'lang' => $bean->lang,
            'dob' => $bean->dob,
            'uaeLicenseHeldFor' => $bean->uae_license_held_for_id,
            'emirateOfRegistration' => $emirateOfRegistration,
            'carTypeInsurance' => $typeOfCarIns,
            'claimHistory' => $claimsHistory,
            'referenceUrl' => $refUrl,
            'device' => $bean->device,
            'additionalNotes' => $bean->additional_notes,
            'reviverName' => $bean->reviver_name,
            'promoCode' => $bean->promo_code,
        ];

        $dataCentr = array_filter($data);

        $formName = 'CAR FORM';
        $insuranceName = 'Car Insurance';
        $emailDataCenter = $data;

        $chCenter = curl_init($centralApi);
        curl_setopt($chCenter, CURLOPT_CONNECTTIMEOUT, 15);
        $dataCentrProcess = json_encode($dataCentr);
        curl_setopt($chCenter, CURLOPT_POSTFIELDS, $dataCentrProcess);
        curl_setopt($chCenter, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($chCenter, CURLOPT_HTTPHEADER, ['x-api-token: '.$centralApiToken.'', 'Content-Type: application/json']);
        curl_setopt($chCenter, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chCenter, CURLOPT_TIMEOUT, 15);
        $resultCentr = curl_exec($chCenter);
        $curlErrnoCentr = curl_errno($chCenter);
        $curlErrorCentr = curl_error($chCenter);

        if ($curlErrnoCentr > 0) {
            $curlMesg = "cURL Error ($curlErrnoCentr): $curlErrorCentr\n";
        } else {
            $curlMesg = "Data received: $resultCentr\n";
        }

        $emailCntrData = '';
        foreach ($emailDataCenter as $key => $value) {
            $emailCntrData .= $key.': '.$value;
            $emailCntrData .= '<pre>';
        }

        $infoCentr = curl_getinfo($chCenter);
        $subject = $emailSys.' CENTRAL API ERROR | '.$formName.' | '.\Request::url().' | '.date('d-m-Y H:i:s');
        if ($infoCentr['http_code'] != 200 || $curlErrnoCentr > 0) {
            Mail::send(['html' => 'apiemail'], [
                'refUrl' => $refUrl,
                'insuranceName' => $insuranceName,
                'curlErrnoCentr' => $curlErrnoCentr,
                'curlErrorCentr' => $curlErrorCentr,
                'curlMesg' => $curlMesg,
                'emailCntrData' => $emailCntrData,
            ], function ($message) use ($subject) {
                $message->to(['adeel.rehman@afia.ae'])->subject($subject);
                $message->from('alfred@insurancemarket.ae', 'Alfred - Error');
            });
        }
        \Log::channel('customlog')->info($curlMesg);
        curl_close($chCenter);
    }

    public function healthPlanCreateQuote(Request $request)
    {
        $planData = [
            'quoteUID' => $request->quoteUID,
            'update' => false,
        ];

        $planData['plans'][] = [
            'planId' => $request->planId,
            'actualPremium' => (float) $request->actualPremium,
            'discountPremium' => 0,
            'isManualUpdate' => false,
            'isManualPremium' => true,
        ];

        $response = $this->healthQuoteService->renewalCreatePlan($planData);

        return $response;
    }

    public function healthPlanUpdateManualProcess(Request $request)
    {
        $response = $this->healthQuoteService->healthPlanModify($request);

        $message = '';
        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            $message = 'Plan has been updated';
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Plan has not been updated '.$responseMessage;
        }

        return $message;
    }

    public function plansByInsuranceProvider(Request $request)
    {
        $insuranceProviderId = $request->insuranceProviderId;
        $quoteUuId = $request->quoteUuId;

        $quotePlans = $this->healthQuoteService->getQuotePlans($quoteUuId);

        $quotePlanId = [];
        $listQuotePlans = [];
        if (isset($quotePlans->quotes->plans)) {
            $listQuotePlans = $quotePlans->quotes->plans;
        }

        foreach ($listQuotePlans as $key => $quotePlan) {
            if (! isset($quotePlan->id)) {
                continue;
            }

            $quotePlanId[] = $quotePlan->id;
        }

        $healthPlans = $this->healthQuoteService->getNonQuotedHealthPlans($insuranceProviderId, $quotePlanId);

        return response()->json($healthPlans);
    }

    // TODO: Code Refactor
    public function cardsView(Request $request)
    {
        $quotes = [];
        $quotes[] = [
            'id' => 8,
            'title' => 'New Lead',
            'data' => getDataAgainstStatus('Health', 8),
        ];
        $quotes[] = [
            'id' => 2,
            'title' => 'Quoted',
            'data' => getDataAgainstStatus('Health', 2),
        ];
        $quotes[] = [
            'id' => 31,
            'title' => 'Qualified',
            'data' => getDataAgainstStatus('Health', 31),
        ];
        $quotes[] = [
            'id' => 25,
            'title' => 'In Negotiation',
            'data' => getDataAgainstStatus('Health', 25),
        ];
        $quotes[] = [
            'id' => 26,
            'title' => 'Application Pending',
            'data' => getDataAgainstStatus('health', 26),
        ];
        $quotes[] = [
            'id' => 28,
            'title' => 'Payment Pending',
            'data' => getDataAgainstStatus('Health', 28),
        ];
        $quotes[] = [
            'id' => 36,
            'title' => 'Application Submitted',
            'data' => getDataAgainstStatus('Health', 36),
        ];
        $quotes[] = [
            'id' => 15,
            'title' => 'Transaction Approved',
            'data' => getDataAgainstStatus('Health', 15),
        ];
        $quotes[] = [
            'id' => 29,
            'title' => 'Policy Documents Pending',
            'data' => getDataAgainstStatus('Health', 29),
        ];

        return inertia('HealthQuote/Cards', [
            'quotes' => $quotes,
        ]);
    }
}
