<?php

namespace App\Services;

use App\Models\HomeQuote;
use Illuminate\Http\Request;
use DB;
use Config;
use Illuminate\Support\Facades\Auth;

class HomeQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = "
                SELECT hqr.id
                ,hqr.first_name
                ,hqr.last_name
                ,hqr.email
                ,hqr.mobile_no
                ,hqr.address
                ,hqr.has_contents
                ,hqr.contents_aed
                ,hqr.has_personal_belongings
                ,hqr.personal_belongings_aed
                ,hqr.has_building
                ,hqr.building_aed
                ,hqr.ilivein_accommodation_type_id
                ,hat.TEXT AS ilivein_accommodation_type_id_text
                ,hqr.iam_possesion_type_id
                ,hpt.TEXT AS iam_possesion_type_id_text
            FROM central_afia.home_quote_request hqr
            INNER JOIN home_accommodation_type hat ON hat.id = hqr.ilivein_accommodation_type_id
            INNER JOIN home_possession_type hpt ON hpt.id = hqr.iam_possesion_type_id";
    }

    public function getEntity($id)
    {
        return DB::select($this->query . ' where hqr.id = ' . $id);
    }

    public function saveHomeQuote(Request $request)
    {
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "contentsAed" => $request->contents_aed,
            "iamPossesionTypeId" => $request->iam_possesion_type_id,
            "iliveinAccommodationTypeId" => $request->ilivein_accommodation_type_id,
            "personalBelongingsAed" => $request->personal_belongings_aed,
            "buildingAed" => $request->building_aed,
            "hasContents" => $request->has_contents == 'on' ?  true : false,
            "nationalityId" => $request->nationality_id,
            "hasBuilding" => $request->has_building == 'on' ? true : false,
            "hasPersonalBelongings" => $request->has_personal_belongings == 'on' ?  true : false,
        );
        if(Auth::user()->hasRole("HOME_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return $this->sendCAPIRequest('/api/v1-save-home-quote', $dataArr);
    }

    public function getGridData($searchProperties, $request)
    {
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $suffix = '';
                    switch ($item) {
                        case 'ilivein_accommodation_type':
                            $suffix = 'hat';
                            break;
                        case 'iam_possesion_type':
                            $suffix = 'hpt';
                            break;
                        default:
                            $suffix = 'hqr';
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

    public function getLeadsForAssignment()
    {
        return HomeQuote::orderBy('created_at', 'desc')->get();
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $isAdvisor = Auth::user()->hasRole(strtoupper($lead_type) . '_ADVISOR');
        $query = "SELECT hqr.id
                            ,hqr.first_name
                            ,hqr.last_name
                            ,hqr.created_at
                            ,u.name AS advisor_name
                            ,'Home' as lead_type
                            ,u.id as advisor_id
                            ,qs.text as lead_status
                        FROM home_quote_request hqr
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

    public function updateHomeQuote(Request $request, $id)
    {
        $homeQuote = HomeQuote::find($id);
        $homeQuote->first_name = $request->first_name;
        $homeQuote->last_name = $request->last_name;
        $homeQuote->email = $request->email;
        $homeQuote->address = $request->address;
        $homeQuote->mobile_no = $request->mobile_no;
        $homeQuote->contents_aed = $request->contents_aed;
        $homeQuote->iam_possesion_type_id = $request->iam_possesion_type_id;
        $homeQuote->ilivein_accommodation_type_id = $request->ilivein_accommodation_type_id;
        $homeQuote->personal_belongings_aed = $request->personal_belongings_aed;
        $homeQuote->building_aed = $request->building_aed;
        $homeQuote->has_contents = $request->has_contents == 'on' ?  true : false;
        $homeQuote->nationality_id = $request->nationality_id;
        $homeQuote->has_building = $request->has_building == 'on' ? true : false;
        $homeQuote->has_personal_belongings = $request->has_personal_belongings == 'on' ?  true : false;
        $homeQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/home/" . $homeQuote->id)->with('success', 'Home Quote has been updated');
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "contents_aed" => "input|number|required",
            "personal_belongings_aed" => "input|number|required",
            "building_aed" => "input|number|required",
            "iam_possesion_type_id" => "select|title|required",
            "ilivein_accommodation_type_id" => "select|title|required",
            "has_contents" => "input|checkbox|required",
            "has_personal_belongings" => "input|checkbox|required",
            "has_building" => "input|checkbox|required",
            "address" => 'textarea|required',
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'iam_possesion_type_id':
                $title = "I am";
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
            "create" => "id",
            "list" => "email,address,iam_possesion_type_id,ilivein_accommodation_type_id",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["email", 'first_name', 'last_name', 'iam_possesion_type_id', 'ilivein_accommodation_type_id'];
    }

    public function getValidationArray($modelPropertiesList, $request)
    {
        $validationArray = [];
        foreach ($modelPropertiesList as $propertyName => $propertyValue) {

            if ($propertyName == 'contents_aed' || $propertyName ==  'personal_belongings_aed' || $propertyName == 'building_aed' || $propertyName == 'has_contents' || $propertyName == 'has_personal_belongings' || $propertyName == 'has_building') {
                if ($request['iam_possesion_type_id'] == null) {
                    $validationArray['has_contents'] = 'required';
                }
                if ($request['iam_possesion_type_id'] == "1") {
                    if ($request['has_building'] == null) {
                        $validationArray['has_contents'] = 'required';
                    }
                    if ($request['has_contents'] == null) {
                        $validationArray['has_building'] = 'required';
                    }
                    if ($request['has_contents'] == 'on') {
                        $validationArray['contents_aed'] = 'required';
                    }
                    if ($request['has_building'] == 'on') {
                        $validationArray['building_aed'] = 'required';
                    }
                    if ($request['has_personal_belongings'] == 'on') {
                        $validationArray['personal_belongings_aed'] = 'required';
                    }
                }

                if ($request['iam_possesion_type_id'] == "2") {
                    $validationArray['has_contents'] = 'required';
                    if ($request['has_contents'] == 'on') {
                        $validationArray['contents_aed'] = 'required';
                    }

                    if ($request['has_personal_belongings'] == 'on') {
                        $validationArray['personal_belongings_aed'] = 'required';
                    }
                }
            } else {
                if ($propertyName != 'id') {
                    $validationArray[$propertyName] = 'required';
                }
            }
        }
        return $validationArray;
    }

    public function getEntityPlain($id)
    {
        return HomeQuote::find($id);
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
