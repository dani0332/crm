<?php

namespace App\Services;

use App\Models\BusinessQuote;
use Illuminate\Http\Request;
use DB;
use Config;
use Auth;

class BusinessQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = "
                            SELECT bqr.id
                            ,bqr.first_name
                            ,bqr.last_name
                            ,bqr.email
                            ,bqr.mobile_no
                            ,bqr.company_name
                            ,bqr.brief_details
                            ,bqr.business_type_of_insurance_id
                            ,bti.TEXT AS business_type_of_insurance_id_text
                            ,bqr.advisor_id
                            ,u.name as advisor_id_text
                        FROM central_afia.business_quote_request bqr
                        LEFT OUTER JOIN business_type_of_insurance bti ON bti.id = bqr.business_type_of_insurance_id
                        LEFT OUTER JOIN users u ON u.id = bqr.advisor_id";
    }

    public function getEntity($id)
    {
        return DB::select($this->query . ' where bqr.id = ' . $id);
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $isAdvisor = Auth::user()->hasRole(strtoupper($lead_type) . '_ADVISOR');
        $query = "SELECT hqr.id
                            ,hqr.first_name
                            ,hqr.last_name
                            ,hqr.created_at
                            ,u.name AS advisor_name
                            ,'Business' as lead_type
                        FROM business_quote_request hqr
                        LEFT OUTER JOIN users u ON u.id = hqr.advisor_id";
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
        if ($isAdvisor) {
            $query .= ' ' . $count == 0 ? ' where' . ' hqr.advisor_id = ' . Auth::user()->id : ' and' . ' hqr.advisor_id = ' . Auth::user()->id;
            $count++;
        }
        return DB::select($query);
    }

    public function getEntityPlain($id)
    {
        return BusinessQuote::find($id);
    }

    public function getLeadsForAssignment()
    {
        return BusinessQuote::orderBy('created_at', 'desc')->get();
    }

    public function saveBusinessQuote(Request $request)
    {
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "companyName" => $request->company_name,
            "briefDetails" => $request->brief_details,
            "businessTypeOfInsuranceId" => $request->business_type_of_insurance_id,
        );
        if(Auth::user()->hasRole("BUSINESS_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return $this->sendCAPIRequest('/api/v1-save-business-quote', $dataArr);
    }

    public function getGridData($searchProperties, $request)
    {
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $suffix = '';
                    switch ($item) {
                        case 'business_type_of_insurance':
                            $suffix = 'bti';
                            break;
                        case 'advisor':
                            $suffix = 'u';
                            break;
                        default:
                            $suffix = 'bqr';
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

    public function updateBusinessQuote(Request $request, $id)
    {
        $businessQuote = BusinessQuote::find($id);
        $businessQuote->first_name = $request->first_name;
        $businessQuote->last_name = $request->last_name;
        $businessQuote->email = $request->email;
        $businessQuote->mobile_no = $request->mobile_no;
        $businessQuote->company_name = $request->company_name;
        $businessQuote->brief_details = $request->brief_details;
        $businessQuote->business_type_of_insurance_id = $request->business_type_of_insurance_id;
        $businessQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/business/" . $businessQuote->id)->with('success', 'Business Quote has been updated');
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "company_name" => "input|text|required",
            "business_type_of_insurance_id" => "select|title|required",
            "brief_details" => 'textarea|required',
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'business_type_of_insurance_id':
                $title = "Business Insurance Type";
                break;
            case 'ilivein_accommodation_type_id':
                $title = "I Live In";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,advisor_id",
            "list" => "email",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["email", 'first_name', 'last_name', 'iam_possesion_type_id', 'ilivein_accommodation_type_id'];
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
