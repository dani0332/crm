<?php

namespace App\Services;

use App\Models\HealthQuote;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Config;

class HealthQuoteService extends BaseService
{
    protected $query;
    public function __construct()
    {
        $this->query = "
        SELECT hqr.id
        ,hqr.uuid
        ,hqr.first_name
        ,hqr.last_name
        ,hqr.email
        ,hqr.mobile_no
        ,hqr.preference
        ,hqr.details
        ,hqr.source
        ,hqr.dob
        ,hqr.has_dental
        ,hqr.has_home
        ,hqr.has_worldwide_cover
        ,hqr.marital_status_id
        ,ms.TEXT AS marital_status_id_text
        ,hqr.cover_for_id
        ,hcf.TEXT AS cover_for_id_text
        ,hqr.nationality_id
        ,n.TEXT AS nationality_id_text
        ,hqr.emirate_of_your_visa_id
        ,e.TEXT AS emirate_of_your_visa_id_text
        ,hqr.advisor_id
        ,u.name as advisor_id_text
    FROM health_quote_request hqr
    LEFT OUTER JOIN marital_status ms ON ms.id = hqr.marital_status_id
    LEFT OUTER JOIN health_cover_for hcf ON hcf.id = hqr.cover_for_id
    LEFT OUTER JOIN nationality n ON n.id = hqr.nationality_id
    LEFT OUTER JOIN emirates e ON e.id = hqr.emirate_of_your_visa_id
    LEFT OUTER JOIN users u on u.id = hqr.advisor_id";
    }

    public function getEntity($id)
    {
        return DB::select($this->query . ' where hqr.uuid =  "' . $id.'"');
    }

    public function getEntityPlain($id)
    {
        return HealthQuote::where('uuid', $id);
    }
    public function getLeadsForAssignment()
    {
        return HealthQuote::orderBy('created_at', 'desc')->get();
    }
    public function saveHealthQuote(Request $request)
    {
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "details" => $request->details,
            "mobileNo" => $request->mobile_no,
            "preference" => $request->preference,
            "source" => $request->source,
            "maritalStatusId" => $request->marital_status_id,
            "dob" => $request->dob,
            "coverForId" => $request->cover_for_id,
            "nationalityId" => $request->nationality_id,
            "hasDental" => $request->has_dental == 'on' ? true : false,
            "hasWorldwideCover" => $request->has_worldwide_cover == 'on' ?  true : false,
            "hasHome" => $request->has_home == 'on' ? true : false,
            "emirateOfYourVisaId" => $request->emirate_of_your_visa_id,
        );
        if(Auth::user()->hasRole("HEALTH_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return $this->sendCAPIRequest('/api/v1-save-health-quote', $dataArr);
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

    public function getGridData($searchProperties, $request)
    {
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $suffix = '';
                    switch ($item) {
                        case 'marital_status_id':
                            $suffix = 'ms';
                            break;
                        case 'health_cover_for':
                            $suffix = 'hcf';
                            break;
                        case 'nationality':
                            $suffix = 'n';
                            break;
                        case 'emirates':
                            $suffix = 'e';
                            break;
                        case 'advisor':
                            $suffix = 'u';
                            break;
                        default:
                            $suffix = 'hqr';
                            break;
                    }
                    $this->query = $this->query . $count > 0 ? ' and' : ' where ' . $suffix . '.' . $item . '=' . "'" . $request[$item] . "'";
                    $count++;
                }
            }
        }
        #dd($this->query);
        return DB::select($this->query);
    }

    public function updateHealthQuote(Request $request, $id)
    {
        $updateArray = [
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'details'=>$request->details,
            'preference' => $request->preference,
            'source' => $request->source,
            'marital_status_id' => $request->marital_status_id,
            'dob' => $request->dob,
            'cover_for_id' => $request->cover_for_id,
            'nationality_id' => $request->nationality_id,
            'has_dental' => $request->has_dental == 'on' ? true : false,
            'has_worldwide_cover' => $request->has_worldwide_cover == 'on' ? true : false,
            'has_home' => $request->has_home == 'on' ? true : false,
            'emirate_of_your_visa_id' => $request->emirate_of_your_visa_id,
        ];
        if(!Auth::user()->hasRole('HOME_ADVISOR')){
            $updateArray['email'] = $request->email;
            $updateArray['mobile_no'] = $request->mobile_no;
        }
        HealthQuote::where('uuid',$id)->update($updateArray);

        if (isset($request->return_to_view))
            return redirect("quote/health/" . $healthQuote->id)->with('success', 'Health Quote has been updated');
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $isAdvisor = Auth::user()->hasRole(strtoupper($lead_type) . '_ADVISOR');
        $isManager = Auth::user()->hasRole('MANAGER');
        if ($isManager) {
            $userIds = Auth::user()->getTeamUserIds();
        }
        $query = "SELECT hqr.id
                            ,hqr.uuid
                            ,hqr.first_name
                            ,hqr.last_name
                            ,hqr.created_at
                            ,u.name AS advisor_name
                            ,'Health' as lead_type
                            ,u.id as advisor_id
                            ,qs.text as lead_status
                        FROM health_quote_request hqr
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
            "preference" => "input|text",
            "email" => "input|email|required",
            "details" => "input|text",
            "mobile_no" => "input|title|number|required",
            "source" => "input|text|title",
            "dob" => 'input|date|required',
            "marital_status_id" => "select|title|required",
            "cover_for_id" => "select|title|required",
            "nationality_id" => "select|title|required",
            "advisor_id" => "select|title|required",
            "has_dental" => "input|checkbox|title",
            "has_worldwide_cover" => "input|checkbox|title",
            "has_home" => "input|checkbox|title",
            "emirate_of_your_visa_id" => "select|title|required"
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'marital_status_id':
                $title = "Marital Status";
                break;
            case 'cover_for_id':
                $title = "Who would you like cover for?";
                break;
            case 'nationality_id':
                $title = "Nationality";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'has_dental':
                $title = "Dental";
                break;
            case 'has_worldwide_cover':
                $title = "WorldWide Cover";
                break;
            case 'has_home':
                $title = "Home Country Cover";
                break;
            case 'advisor_id':
                $title = "Advisor";
                break;
            case 'source':
                $title = "Lead Source";
                break;
            case 'emirate_of_your_visa_id':
                $title = "Emirate of your visa";
                break;
            case 'dob':
                $title = "Date of Birth";
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
            "list" => "email,cover_for_id,has_worldwide_cover,has_home,details,preference",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["email", 'first_name', 'last_name', 'nationality_id', 'advisor_id'];
    }
}
