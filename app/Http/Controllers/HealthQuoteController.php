<?php

namespace App\Http\Controllers;

use App\Models\HealthQuote;
use Config;
use DataTables;
use Illuminate\Http\Request;

class HealthQuoteController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {
        $this->middleware('permission:health-quotes-list|health-quotes-resubmit-api', ['only' => ['index', 'store']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = HealthQuote::select('*');
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
        $centralApi = Config::get('constants.central_api_endpoint') . $carQuoteSaveApi;
        $centralApiToken = Config::get('constants.central_api_token');

        $carValue = $bean->car_value == "?" ? "0" : $bean->car_value;

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

        $data = array(
            "firstName" => $bean->first_name,
            "lastName" => $bean->last_name,
            "email" => $bean->email,
            "mobileNo" => $bean->mobile_no,
            "nationality" => $nationality,
            "carValue" => $carValue,
            "currentlyInsuredWith" => $bean->currently_insured_with,
            "carMakeCode" => $carMakeCode,
            "carModelCode" => $carModelCode,
            "yearOfManufacture" => $bean->year_of_manufacture,
            "source" => $bean->source,
            "lang" => $bean->lang,
            "dob" => $bean->dob,
            "uaeLicenseHeldFor" => $bean->uae_license_held_for_id,
            "emirateOfRegistration" => $emirateOfRegistration,
            "carTypeInsurance" => $typeOfCarIns,
            "claimHistory" => $claimsHistory,
            "referenceUrl" => $refUrl,
            "device" => $bean->device,
            "additionalNotes" => $bean->additional_notes,
            "reviverName" => $bean->reviver_name,
            "promoCode" => $bean->promo_code,
        );

        $dataCentr = array_filter($data);

        $formName = 'CAR FORM';
        $insuranceName = 'Car Insurance';
        $emailDataCenter = $data;

        $chCenter = curl_init($centralApi);
        curl_setopt($chCenter, CURLOPT_CONNECTTIMEOUT, 15);
        $dataCentrProcess = json_encode($dataCentr);
        curl_setopt($chCenter, CURLOPT_POSTFIELDS, $dataCentrProcess);
        curl_setopt($chCenter, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($chCenter, CURLOPT_HTTPHEADER, array('x-api-token: ' . $centralApiToken . '', 'Content-Type: application/json'));
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
            $emailCntrData .= $key . ': ' . $value;
            $emailCntrData .= "<pre>";
        }

        $infoCentr = curl_getinfo($chCenter);
        $subject = $emailSys . " CENTRAL API ERROR | " . $formName . " | " . \Request::url() . " | " . date('d-m-Y H:i:s');
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
}
