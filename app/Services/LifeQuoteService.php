<?php

namespace App\Services;

use App\Models\LifeQuote;
use Illuminate\Http\Request;
use DB;
use Auth;

class LifeQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = "
                        SELECT lqr.id
                        ,lqr.first_name
                        ,lqr.last_name
                        ,lqr.email
                        ,lqr.mobile_no
                        ,lqr.gender
                        ,lqr.dob
                        ,lqr.is_smoker
                        ,lqr.others_info
                        ,lqr.sum_insured_value
                        ,lqr.sum_insured_currency_id
                        ,ct.TEXT AS sum_insured_currency_id_text
                        ,lqr.marital_status_id
                        ,ms.TEXT AS marital_status_id_text
                        ,lqr.purpose_of_insurance_id
                        ,lip.TEXT AS purpose_of_insurance_id_text
                        ,lqr.children_id
                        ,lc.TEXT AS children_id_text
                        ,lqr.tenure_of_insurance_id
                        ,lit.TEXT AS tenure_of_insurance_id_text
                        ,lqr.number_of_years_id
                        ,liy.TEXT AS number_of_years_id_text
                        ,lqr.nationality_id
                        ,n.TEXT AS nationality_id_text
                    FROM central_afia.life_quote_request lqr
                    INNER JOIN currency_type ct ON ct.id = lqr.sum_insured_currency_id
                    INNER JOIN marital_status ms ON ms.id = lqr.marital_status_id
                    INNER JOIN life_insurance_purpose lip ON lip.id = lqr.purpose_of_insurance_id
                    INNER JOIN life_children lc ON lc.id = lqr.children_id
                    INNER JOIN life_insurance_tenure lit ON lit.id = lqr.tenure_of_insurance_id
                    INNER JOIN life_number_of_year liy ON liy.id = lqr.number_of_years_id
                    INNER JOIN nationality n ON n.id = lqr.nationality_id";
    }
    public function saveLifeQuote(Request $request)
    {
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "dob" => $request->dob,
            "sumInsuredValue" => $request->sum_insured_value,
            "sumInsuredCurrencyId" => $request->sum_insured_currency_id,
            "maritalStatusId" => $request->marital_status_id,
            "purposeOfInsuranceId" => $request->purpose_of_insurance_id,
            "childrenId" => $request->children_id,
            "tenureOfInsuranceId" => $request->tenure_of_insurance_id,
            "numberOfYearsId" => $request->number_of_years_id,
            "isSmoker" => $request->is_smoker == 'Yes' ?  true : false,
            "gender" => $request->gender,
            "othersInfo" => $request->others_info,
        );
        if(Auth::user()->hasRole("LIFE_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return $this->sendCAPIRequest('/api/v1-save-home-quote', $dataArr);

        $lifeQuote = new LifeQuote();
        $lifeQuote->first_name = $request->first_name;
        $lifeQuote->last_name = $request->last_name;
        $lifeQuote->email = $request->email;
        $lifeQuote->details = $request->details;
        $lifeQuote->mobile_no = $request->mobile_no;
        $lifeQuote->travel_cover_for_id = $request->travel_cover_for_id;
        $lifeQuote->nationality_id = $request->nationality_id;
        $lifeQuote->days_cover_for = $request->days_cover_for;
        $lifeQuote->destination = $request->destination;
        $lifeQuote->region_cover_for_id = $request->region_cover_for_id;
        $lifeQuote->details = $request->details;
        $lifeQuote->save();
    }

    public function getEntity($id)
    {
        return DB::select($this->query . ' where lqr.id = ' . $id);
    }

    public function getEntityPlain($id)
    {
        return LifeQuote::find($id);
    }
    public function getLeadsForAssignment()
    {
        return LifeQuote::orderBy('created_at', 'desc')->get();
    }
    public function getGridData($searchProperties, $request)
    {
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $suffix = '';
                    switch ($item) {
                        case 'sum_insured_currency_id':
                            $suffix = 'ct';
                            break;
                        case 'marital_status_id':
                            $suffix = 'ms';
                            break;
                        case 'nationality_id':
                            $suffix = 'n';
                            break;
                        case 'purpose_of_insurance_id':
                            $suffix = 'lip';
                            break;
                        case 'children_id':
                            $suffix = 'lc';
                            break;
                        case 'tenure_of_insurance_id':
                            $suffix = 'lit';
                            break;
                        case 'number_of_years_id':
                            $suffix = 'liy';
                            break;
                        default:
                            $suffix = 'lqr';
                            break;
                    }
                    if ($count == 0) {
                        $this->query = $this->query . ' where ' . $suffix . '.' . $item . '=' . "'" . $request[$item] . "'";
                    } else {
                        $this->query = $this->query . ' and ' . $suffix . '.' . $item . '=' . "'" . $request[$item] . "'";
                    }
                    $count++;
                }
            }
        }

        return DB::select($this->query);
    }

    public function updateLifeQuote(Request $request, $id)
    {
        $lifeQuote = LifeQuote::find($id);
        $lifeQuote->first_name = $request->first_name;
        $lifeQuote->last_name = $request->last_name;
        $lifeQuote->email = $request->email;
        $lifeQuote->details = $request->details;
        $lifeQuote->mobile_no = $request->mobile_no;
        $lifeQuote->dob = $request->dob;
        $lifeQuote->gender = $request->gender == 'Male' ? 'M' : 'F';
        $lifeQuote->is_smoker = $request->is_smoker == 'Yes' ? '1' : '0';
        $lifeQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/life/" . $lifeQuote->id)->with('success', 'Life Quote has been updated');
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $isAdvisor = Auth::user()->hasRole(strtoupper($lead_type) . '_ADVISOR');
        $query = "SELECT hqr.id
                            ,hqr.first_name
                            ,hqr.last_name
                            ,hqr.created_at
                            ,u.name AS advisor_name
                            ,'Life' as lead_type
                            ,u.id as advisor_id
                            ,qs.text as lead_status
                        FROM life_quote_request hqr
                        LEFT OUTER JOIN users u ON u.id = hqr.advisor_id
                        LEFT OUTER JOIN quote_status qs ON qs.id = hqr.quote_status_id
                        ORDER BY u.name";
        $count = 0;
        if (!empty($CDBID)) {
            $query .= ' where hqr.id = ' . $CDBID;
            $count++;
        }
        if (!empty($email)) {
            $query .= ' ' . $count == 0 ? ' where' . ' hqr.email = ' . $email : ' and' . ' hqr.email = ' . $email;
            $count++;
        }
        if (!empty($mobile_no)) {
            $query .= ' ' . $count == 0 ? ' where' . ' hqr.email = ' . $mobile_no : ' and' . ' hqr.email = ' . $mobile_no;
            $count++;
        }
        return DB::select($query);
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "mobile_no" => "input|number|title|required",
            "email" => "input|email|required",
            "dob" => "input|date|title|required",
            "sum_insured_value" => "input|number|title||required",
            "sum_insured_currency_id" => "select|title|required",
            "purpose_of_insurance_id" => "select|title|required",
            "marital_status_id" => "select|title|required",
            "children_id" => "select|title|required",
            "tenure_of_insurance_id" => "select|title|required",
            "number_of_years_id" => "select|title|required",
            "gender" => "|static|required|Male,Female",
            "is_smoker" => "|static|title|required|Yes,No",
            "others_info" => "textarea",
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'purpose_of_insurance_id':
                $title = "Purpose of Insurance";
                break;
            case 'children_id':
                $title = "Children";
                break;
            case 'tenure_of_insurance_id':
                $title = "Tenure Of Insurance";
                break;
            case 'number_of_years_id':
                $title = "No. of Years";
                break;
            case 'sum_insured_currency_id':
                $title = "Currency";
                break;
            case 'dob':
                $title = "Date Of Birth";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'is_smoker':
                $title = "Smoker";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'sum_insured_value':
                $title = "Sum Insured Value";
                break;
            case 'marital_status_id':
                $title = "Marital Status";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id",
            "list" => "email,mobile_no,others_info",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["email", 'first_name', 'last_name', 'nationality_id', 'region_cover_for_id', 'travel_cover_for_id'];
    }

    public function sendCAPIRequest($endpoint, $data)
    {
        $apiEndPoint = Config::get('constants.CENTRAL_API_ENDPOINT') . $endpoint;
        $apiToken = Config::get('constants.CENTRAL_API_TOKEN');
        $apiTimeout = Config::get('constants.CENTRAL_API_TIMEOUT');

        $client = new \GuzzleHttp\Client();
        $capiRequest = $client->post(
            $apiEndPoint,
            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'x-api-token' => $apiToken],
                'body' => json_encode($data),
                'timeout' => $apiTimeout,
            ]
        );

        $getStatusCode = $capiRequest->getStatusCode();

        if ($getStatusCode == 200) {
            $getContents = $capiRequest->getBody();
            $getdecodeContents = json_decode($getContents);
            return $getdecodeContents;
        } else {
            return "API failed";
        }
    }
}
