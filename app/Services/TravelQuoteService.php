<?php

namespace App\Services;
use App\Models\TravelQuote;
use Illuminate\Http\Request;
use DB;
use Config;
class TravelQuoteService extends BaseService
{

    protected $query;
    public function __construct()
    {
        $this->query = "
                        SELECT tqr.id
                        ,tqr.days_cover_for
                        ,tqr.details
                        ,tqr.destination
                        ,tqr.travel_cover_for_id
                        ,tcf.TEXT AS travel_cover_for_id_text
                        ,tqr.first_name
                        ,tqr.last_name
                        ,tqr.email
                        ,tqr.mobile_no
                        ,tqr.nationality_id
                        ,n.TEXT AS nationality_id_text
                        ,tqr.region_cover_for_id
                        ,r.TEXT AS region_cover_for_id_text
                    FROM central_afia.travel_quote_request tqr
                    INNER JOIN travel_cover_for tcf ON tcf.id = tqr.travel_cover_for_id
                    INNER JOIN nationality n ON n.id = tqr.nationality_id
                    INNER JOIN region r ON r.id = tqr.region_cover_for_id";
    }

	public function saveTravelQuote(Request $request)
	{
        $travelQuote = new TravelQuote();
        $travelQuote->first_name = $request->first_name;
        $travelQuote->last_name = $request->last_name;
        $travelQuote->email = $request->email;
        $travelQuote->details = $request->details;
        $travelQuote->mobile_no = $request->mobile_no;
        $travelQuote->travel_cover_for_id = $request->travel_cover_for_id;
        $travelQuote->nationality_id = $request->nationality_id;
        $travelQuote->days_cover_for = $request->days_cover_for;
        $travelQuote->destination = $request->destination;
        $travelQuote->region_cover_for_id = $request->region_cover_for_id;
        $travelQuote->details = $request->details;
        $travelQuote->save();

        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "details" => $request->details,
            "mobileNo" => $request->mobile_no,
            "travelCoverForId" => $request->travel_cover_for_id,
            "nationalityId" => $request->nationality_id,
            "daysCoverFor" => $request->days_cover_for,
            "destination" => $request->destination,
            "regionCoverForId" => $request->region_cover_for_i,
        );
        return $this->sendCAPIRequest('/api/v1-save-travel-quote', $dataArr);
	}

    public function sendCAPIRequest($endpoint, $data){
        $apiEndPoint = Config::get('constants.CENTRAL_API_ENDPOINT').$endpoint;
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

        if($getStatusCode == 200) {
            $getContents = $capiRequest->getBody();
            $getdecodeContents = json_decode($getContents);
            return $getdecodeContents;
        }
        else {
            return "API failed";
        }
    }

    public function getGridData($searchProperties, $request){
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if(!empty($request[$item])){
                    $suffix = '';
                    switch ($item) {
                        case 'travel_cover_for':
                            $suffix = 'tcf';
                            break;
                        case 'region':
                            $suffix = 'r';
                            break;
                        case 'nationality':
                            $suffix = 'n';
                            break;
                        default:
                            $suffix = 'tqr';
                            break;
                    }
                    if($count == 0){
                        $this->query = $this->query.' where '.$suffix.'.'.$item.'='."'".$request[$item]."'";
                    }else{
                        $this->query = $this->query.' and '.$suffix.'.'.$item.'='."'".$request[$item]."'";
                    }
                    $count++;
                }
            }
        }
        return DB::select($this->query);
    }

    public function getEntity($id){
        return DB::select($this->query.' where tqr.id = '. $id);
    }

    public function getEntityPlain($id){
        return TravelQuote::find($id);
    }

    public function updateTravelQuote(Request $request, $id)
	{
        $travelQuote = TravelQuote::find($id);
        $travelQuote->first_name = $request->first_name;
        $travelQuote->last_name = $request->last_name;
        $travelQuote->email = $request->email;
        $travelQuote->details = $request->details;
        $travelQuote->mobile_no = $request->mobile_no;
        $travelQuote->travel_cover_for_id = $request->travel_cover_for_id;
        $travelQuote->nationality_id = $request->nationality_id;
        $travelQuote->days_cover_for = $request->days_cover_for;
        $travelQuote->destination = $request->destination;
        $travelQuote->region_cover_for_id = $request->region_cover_for_id;
        $travelQuote->details = $request->details;
        $travelQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/travel/" . $travelQuote->id)->with('success', 'Travel Quote has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "mobile_no" => "input|text|title|required",
            "email" => "input|email|required",
            "mobile_no" => "input|number|title|required",
            "days_cover_for" => "input|number|title|required",
            "destination" => "input|text|required",
            "nationality_id" => "select|title|required",
            "region_cover_for_id" => "select|title|required",
            "travel_cover_for_id" => "select|title|required",
            "details" => "textarea|text|required"
        );
    }

    public function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'days_cover_for':
                $title = "How many days would you like cover for?";
                break;
            case 'region_cover_for_id':
                $title = "Which regions do you need cover for?";
                break;
            case 'nationality_id':
                $title = "Nationality";
                break;
            case 'travel_cover_for_id':
                $title = "Who would you like cover for?";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties() {
        return [
            "create" => "id",
            "list" => "email,mobile_no",
        ];
    }

    public function fillModelSearchProperties(){
        return ["email", 'first_name', 'last_name', 'nationality_id', 'region_cover_for_id', 'travel_cover_for_id'];
    }
}
