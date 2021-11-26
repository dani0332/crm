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
        $this->query = "
            SELECT
            cqr.uuid
            ,cqr.id
            ,cqr.first_name
            ,cqr.last_name
            ,cqr.email
            ,cqr.mobile_no
            ,cqr.dob
            ,cqr.car_value
            ,cqr.additional_notes
            ,cqr.nationality_id
            ,cqr.year_of_manufacture
            ,cqr.premium
            ,cqr.paid_at
            ,n.TEXT AS nationality_id_text
            ,cqr.uae_license_held_for_id
            ,ulhf.TEXT AS uae_license_held_for_id_text
            ,cqr.car_make_id
            ,cmake.TEXT AS car_make_id_text
            ,cqr.car_model_id
            ,cmodel.TEXT AS car_model_id_text
            ,cqr.emirate_of_registration_id
            ,e.TEXT AS emirate_of_registration_id_text
            ,ip.id AS currently_insured_with
            ,ip.TEXT AS currently_insured_with_text
            ,cqr.car_type_insurance_id
            ,cti.TEXT AS car_type_insurance_id_text
            ,cqr.claim_history_id
            ,ch.TEXT AS claim_history_id_text
            ,cqr.advisor_id
            ,u.name AS advisor_id_text
            ,cqr.payment_status_id
            ,ps.text AS payment_status_id_text
        FROM car_quote_request cqr
        LEFT OUTER JOIN nationality n ON n.id = cqr.nationality_id
        LEFT OUTER JOIN uae_license_held_for ulhf ON ulhf.id = cqr.uae_license_held_for_id
        LEFT OUTER JOIN car_make cmake ON cmake.id = cqr.car_make_id
        LEFT OUTER JOIN car_model cmodel ON cmodel.id = cqr.car_model_id
        LEFT OUTER JOIN emirates e ON e.id = cqr.emirate_of_registration_id
        LEFT OUTER JOIN insurance_provider ip ON ip.TEXT = cqr.currently_insured_with
        LEFT OUTER JOIN car_type_insurance cti ON cti.id = cqr.car_type_insurance_id
        LEFT OUTER JOIN claim_history ch ON ch.id = cqr.claim_history_id
        LEFT OUTER JOIN users u ON u.id = cqr.advisor_id
        LEFT OUTER JOIN payment_status ps ON ps.id = cqr.payment_status_id";
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
        return DB::select($this->query . ' where cqr.uuid = "' . $id . '"');
    }

    public function getEntityPlain($id)
    {
        return CarQuote::where('uuid', $id);
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
            "nationality_id" => "select|title|required",
            "uae_license_held_for_id" => "select|title|required",
            "car_make_id" => "select|title|required",
            "car_model_id" => "select|title|required",
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
            case 'advisor_id':
                $title = "Advisor";
                break;
            case 'payment_status_id':
                $title = "Payment Status";
                break;
            default:
                break;
        }
        return $title;
    }

    public function getGridData($searchProperties, $request)
    {
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $suffix = '';
                    switch ($item) {
                        case 'uae_license_held_for':
                            $suffix = 'ulhf';
                            break;
                        case 'car_make':
                            $suffix = 'cmake';
                            break;
                        case 'car_model':
                            $suffix = 'cmodel';
                            break;
                        case 'nationality':
                            $suffix = 'n';
                            break;
                        case 'emirates':
                            $suffix = 'e';
                            break;
                        case 'insurance_provider':
                            $suffix = 'ip';
                            break;
                        case 'claim_history':
                            $suffix = 'ch';
                            break;
                        case 'car_type_insurance':
                            $suffix = 'cti';
                            break;
                        case 'advisor':
                            $suffix = 'u';
                            break;
                        default:
                            $suffix = 'cqr';
                            break;
                    }
                    $this->query = $this->query . ($count == 0 ? ' where ' : ' and ') . $suffix . '.' . $item . '=' . "'" . $request[$item] . "'";
                    $count++;
                }
            }
        }

        $this->query .= ' ORDER BY cqr.created_at DESC';
        return DB::select($this->query);
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $isAdvisor = Auth::user()->hasRole(strtoupper($lead_type) . '_ADVISOR');
        $query = "SELECT hqr.id
                            ,hqr.first_name
                            ,hqr.last_name
                            ,hqr.created_at
                            ,u.name AS advisor_name
                            ,'Car' as lead_type
                        FROM car_quote_request hqr
                        LEFT OUTER JOIN users u ON u.id = hqr.advisor_id";
        $count = 0;
        if (!empty($CDBID)) {
            $query .= ' where hqr.id = ' . $CDBID;
            $count++;
        }
        if (!empty($email)) {
            $query .= ($count == 0 ? ' where ' : ' and ') . ' hqr.email = ' . $email;
            $count++;
        }
        if (!empty($mobile_no)) {
            $query .= ($count == 0 ? ' where ' : ' and '). ' hqr.mobile_no = ' . $mobile_no;
            $count++;
        }
        if ($isAdvisor) {
            $query .= ($count == 0 ? ' where ' : ' and '). ' hqr.advisor_id = ' . Auth::user()->id;
            $count++;
        }
        return DB::select($query);
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,advisor_id,premium,paid_at,payment_status_id",
            "list" => "additional_notes,email,mobile_no,first_name,last_name,currently_insured_with,premium,paid_at,payment_status_id",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["first_name", "last_name", "email", "mobile_no", "nationality_id"];
    }

    public function getQuotePlans($id)
    {
        $quoteUuId = CarQuote::where('uuid', '=', $id)->value('uuid');

        $plansApiEndPoint = Config::get('constants.KEN_PLANS_API_ENDPOINT');
        $plansApiToken = Config::get('constants.KEN_PLANS_API_TOKEN');
        $plansApiTimeout = Config::get('constants.KEN_PLANS_API_TIMEOUT');

        $plansDataArr = array(
            "quoteUID" => $quoteUuId,
            "lang" => "en",
        );

        $client = new \GuzzleHttp\Client();

        try {

            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'x-api-token' => $plansApiToken],
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
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = "API Failed - " . $response->getBody()->getContents();
            return $responseBodyAsString;
        }
    }
}
