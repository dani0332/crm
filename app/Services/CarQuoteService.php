<?php

namespace App\Services;

use App\Models\CarQuote;
use Illuminate\Http\Request;
use Config;
use DB;
use Auth;
use GuzzleHttp\Exception\ClientException;

class CarQuoteService extends BaseService
{
    protected $query;
    public function __construct()
    {
        $this->query = DB::table('car_quote_request as cqr')
        ->select('cqr.uuid','cqr.id'
        ,'cqr.first_name','cqr.last_name'
        ,'cqr.email','cqr.mobile_no','cqr.dob'
        ,'cqr.car_value','cqr.additional_notes'
        ,'cqr.nationality_id','cqr.year_of_manufacture','cqr.code','cqr.is_ecommerce'
        ,'cqr.premium','cqr.paid_at','n.TEXT AS nationality_id_text'
        ,'cqr.uae_license_held_for_id','ulhf.TEXT AS uae_license_held_for_id_text'
        ,'cqr.car_make_id','cmake.TEXT AS car_make_id_text'
        ,'cqr.car_model_id','cmodel.TEXT AS car_model_id_text'
        ,'cqr.emirate_of_registration_id','e.TEXT AS emirate_of_registration_id_text'
        ,'ip.id AS currently_insured_with','ip.TEXT AS currently_insured_with_text'
        ,'cqr.car_type_insurance_id','cti.TEXT AS car_type_insurance_id_text'
        ,'cqr.claim_history_id','ch.TEXT AS claim_history_id_text'
        ,'cqr.advisor_id','u.name AS advisor_id_text'
        ,'cqr.payment_status_id','ps.text AS payment_status_id_text'
        ,'cqr.plan_id','cp.text AS plan_id_text'
        ,'cp.provider_id AS car_plan_provider_id','cpip.text AS car_plan_provider_id_text')
        ->leftJoin('nationality as n', 'n.id', '=', 'cqr.nationality_id')
        ->leftJoin('car_make as cmake', 'cmake.id', '=', 'cqr.car_make_id')
        ->leftJoin('uae_license_held_for as ulhf', 'ulhf.id', '=', 'cqr.uae_license_held_for_id')
        ->leftJoin('car_model as cmodel', 'cmodel.id', '=', 'cqr.car_model_id')
        ->leftJoin('emirates as e', 'e.id', '=', 'cqr.emirate_of_registration_id')
        ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'cqr.currently_insured_with')
        ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'cqr.car_type_insurance_id')
        ->leftJoin('claim_history as ch', 'ch.id', '=', 'cqr.claim_history_id')
        ->leftJoin('users as u', 'u.id', '=', 'cqr.advisor_id')
        ->leftJoin('car_plan as cp', 'cp.id', '=', 'cqr.plan_id')
        ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
        ->leftJoin('payment_status as ps', 'ps.id', '=', 'cqr.payment_status_id');
    }

    public function saveCarQuote(Request $request)
    {
        $carQuote = new CarQuote();
        $carQuote->first_name = $request->first_name;
        $carQuote->last_name = $request->last_name;
        $carQuote->email = $request->email;
        $carQuote->details = $request->details;
        $carQuote->mobile_no = $request->mobile_no;
        $carQuote->preference = $request->preference;
        $carQuote->source = $request->source;
        $carQuote->marital_status_id = $request->marital_status_id;
        $carQuote->dob = $request->dob;
        $carQuote->cover_for_id = $request->cover_for_id;
        $carQuote->nationality_id = $request->nationality_id;
        $carQuote->has_dental = $request->has_dental == 'on' ? 1 : 0;
        $carQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? 1 : 0;
        $carQuote->has_home = $request->has_home == 'on' ? 1 : 0;
        $carQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $carQuote->save();
    }

    public function updateCarQuote(Request $request, $id)
    {
        $carQuote = CarQuote::find($id);
        $carQuote->first_name = $request->first_name;
        $carQuote->last_name = $request->last_name;
        $carQuote->email = $request->email;
        $carQuote->details = $request->details;
        $carQuote->mobile_no = $request->mobile_no;
        $carQuote->preference = $request->preference;
        $carQuote->source = $request->source;
        $carQuote->marital_status_id = $request->marital_status_id;
        $carQuote->dob = $request->dob;
        $carQuote->cover_for_id = $request->cover_for_id;
        $carQuote->nationality_id = $request->nationality_id;
        $carQuote->has_dental = $request->has_dental == 'on' ? 1 : 0;
        $carQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? 1 : 0;
        $carQuote->has_home = $request->has_home == 'on' ? 1 : 0;
        $carQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $carQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/health/" . $carQuote->id)->with('success', 'Health Quote has been updated');
    }

    public function getEntity($id)
    {
        return $this->query->where('cqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return CarQuote::where('id', $id)->first();
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "dob" => "input|title|date|required",
            "code" => "input|title",
            "nationality_id" => "select|title|required",
            "uae_license_held_for_id" => "select|title|required",
            "is_ecommerce" => "|static|title|Yes,No",
            // "car_make_id" => "select|title|required",
            // "car_model_id" => "select|title|required",
            "year_of_manufacture" => "|static|required|2022,2021,2020,2019,2018,2017,2016,2015,2014,2013,2012,2011,2010,2009,2008,2007,2006,2005,2004,2003,2002,2001,2000,1999,1998 or older",
            "emirate_of_registration_id" => "select|title|required",
            "currently_insured_with" => "select|required",
            "car_value" => "number|required",
            "premium" => "number",
            "paid_at" => "input|date",
            "car_type_insurance_id" => "select|title|required",
            "claim_history_id" => "select|title|required",
            "advisor_id" => "select|title|required",
            "additional_notes" => "textarea|required",
            "payment_status_id" => "select|title",
            "plan_id" => "select|title",
            "car_plan_provider_id" => "select|title",
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'dob':
                $title = "Date of Birth";
                break;
            case 'uae_license_held_for_id':
                $title = "UAE licence held for";
                break;
            case 'car_make_id':
                $title = "Car Make";
                break;
            case 'car_model_id':
                $title = "Car Model";
                break;
            case 'nationality_id':
                $title = "Nationality";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'emirate_of_registration_id':
                $title = "Emirate Of Registration";
                break;
            case 'car_type_insurance_id':
                $title = "Type Of Insurance";
                break;
            case 'claim_history_id':
                $title = "Claim History";
                break;
            case 'source':
                $title = "Lead Source";
                break;
            case 'reviver_name':
                $title = "Reviver Name";
                break;
            case 'code':
                $title = "CDB ID";
                break;
            case 'advisor_id':
                $title = "Advisor";
                break;
            case 'payment_status_id':
                $title = "Payment Status";
                break;
            case 'plan_id':
                $title = "Plan Name";
                break;
            case 'car_plan_provider_id':
                $title = "Provider Name";
                break;
            case 'is_ecommerce':
                $title = "Ecommerce";
                break;
            default:
                break;
        }
        return $title;
    }

    public function getGridData($searchProperties, $request)
    {
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $searchedValue = str_contains($request[$item], 'Yes') || str_contains($request[$item], 'No') ? ( $request[$item] == 'Yes' ? 1 : 0 ) : $request[$item];
                    $this->query->where($this->getQuerySuffix($item) . '.' . $item, $searchedValue);
                }
            }
        }
        $this->query->orderBy('cqr.created_at', 'DESC');
        return $this->query;
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'uae_license_held_for':
                return 'ulhf';
                break;
            case 'car_make':
                return 'cmake';
                break;
            case 'car_model':
                return 'cmodel';
                break;
            case 'nationality':
                return 'n';
                break;
            case 'emirates':
                return 'e';
                break;
            case 'insurance_provider':
                return 'ip';
                break;
            case 'claim_history':
                return 'ch';
                break;
            case 'car_type_insurance':
                return 'cti';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'plan':
                return 'cp';
                break;
            case 'car_plan_provider':
                return 'cpip';
                break;
            default:
                return 'cqr';
                break;
        }
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query =  DB::table('car_quote_request as cqr')
                    ->select('cqr.id','cqr.uuid','cqr.first_name','cqr.last_name','cqr.created_at','u.name AS advisor_name',DB::raw("'Car' as lead_type")
                    ,'u.id as advisor_id','qs.text as lead_status')
                    ->leftJoin('users as u', 'u.id', '=', 'cqr.advisor_id')
                    ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
                    ->orderBy('advisor_id', 'ASC');

        if (!empty($CDBID)) {
           $query->where('cqr.CDBID', $CDBID);
        }
        if (!empty($email)) {
            $query->where('cqr.email', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('cqr.mobile_no', $mobile_no);
        }
        return $query;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,advisor_id,premium,paid_at,payment_status_id,plan_id,car_plan_provider_id,code,is_ecommerce",
            "list" => "additional_notes,email,mobile_no,first_name,last_name,currently_insured_with,premium,paid_at,payment_status_id,plan_id,car_plan_provider_id",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["first_name", "last_name", "email", "mobile_no", "nationality_id", "payment_status_id", "code", "is_ecommerce"];
    }

    public function getQuotePlans($id)
    {
        $quoteUuId = CarQuote::where('uuid', '=', $id)->value('uuid');

        $plansApiEndPoint = Config::get('constants.KEN_PLANS_API_ENDPOINT');
        $plansApiToken = Config::get('constants.KEN_PLANS_API_TOKEN');
        $plansApiTimeout = Config::get('constants.KEN_PLANS_API_TIMEOUT');
        $plansApiUserName = Config::get('constants.KEN_PLANS_API_USER');
        $plansApiPassword = Config::get('constants.KEN_PLANS_API_PWD');
        $authBasic = base64_encode($plansApiUserName.":".$plansApiPassword);

        $plansDataArr = array(
            "quoteUID" => $quoteUuId,
            "lang" => "en",
        );

        $client = new \GuzzleHttp\Client();

        try {

            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json',
                    'x-api-token' => $plansApiToken,
                    'Authorization' => 'Basic '.$authBasic],
                    'body' => json_encode($plansDataArr),
                    'timeout' => $plansApiTimeout,
                ]
            );

            $getStatusCode = $kenRequest->getStatusCode();

            if ($getStatusCode == 200) {
                $getContents = $kenRequest->getBody();
                $getdecodeContents = json_decode($getContents);
                return $getdecodeContents;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            if(isset($response->message)) {
                $responseBodyAsString = $response->message;
            }
            else if(isset($response->error)) {
                $responseBodyAsString = $response->error;
            }
            else {
                $responseBodyAsString = $response->msg;
            }

            return $responseBodyAsString;
        }
    }

    public function getCarQuotePlanAddons($id) {

        $listCarQuotePlanAddons = DB::table('car_addon_option')
        ->select('car_addon.text AS car_addon_text','car_addon_option.value AS car_addon_option_value'
        ,'car_addon_option.price AS car_addon_option_price','car_addon.type AS car_addon_type')
        ->leftJoin('car_addon', 'car_addon.id', '=', 'car_addon_option.addon_id')
        ->leftJoin('car_quote_request_addon', 'car_addon_option.id', '=', 'car_quote_request_addon.addon_option_id')
        ->leftJoin('car_quote_request', 'car_quote_request.id', '=', 'car_quote_request_addon.quote_request_id')
        ->where('car_quote_request.uuid', $id)->get();
        return $listCarQuotePlanAddons;
    }
}
