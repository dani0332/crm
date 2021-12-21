<?php

namespace App\Services;

use App\Models\TravelQuote;
use Illuminate\Http\Request;
use DB;
use Auth;

class TravelQuoteService extends BaseService
{

    protected $query;
    public function __construct()
    {
        $this->query = DB::table('travel_quote_request as tqr')->
        select('tqr.id','tqr.uuid', 'tqr.created_at','tqr.updated_at','tqr.code', 'tqr.days_cover_for','tqr.details','tqr.destination','tqr.travel_cover_for_id','tcf.TEXT AS travel_cover_for_id_text'
        ,'tqr.first_name','tqr.last_name','tqr.email' ,'tqr.mobile_no','tqr.nationality_id','n.TEXT AS nationality_id_text'
        ,'qs.id as quote_status_id', 'qs.text as quote_status_id_text', 'u.id as advisor_id', 'u.name as advisor_id_text'
        ,'tqr.region_cover_for_id','r.TEXT AS region_cover_for_id_text')
        ->leftJoin('travel_cover_for as tcf', 'tcf.id', '=', 'tqr.travel_cover_for_id')
        ->leftJoin('nationality as n', 'n.id', '=', 'tqr.nationality_id')
        ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
        ->leftJoin('users as u', 'u.id', '=', 'tqr.advisor_id')
        ->leftJoin('region as r', 'r.id', '=', 'tqr.region_cover_for_id');
    }

    public function saveTravelQuote(Request $request)
    {
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
        if(Auth::user()->hasRole("TRAVEL_ADVISOR")){
            $dataArr['advisorId'] = Auth::user()->id;
        }
        return CapiRequestService::sendCAPIRequest('/api/v1-save-travel-quote', $dataArr);
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('travel_quote_request as tqr')
                    ->select('tqr.id','tqr.uuid','tqr.first_name','tqr.last_name','tqr.created_at','u.name AS advisor_name',DB::raw("'Travel' as lead_type")
                    ,'u.id as advisor_id','qs.text as lead_status')
                    ->leftJoin('users as u', 'u.id', '=', 'tqr.advisor_id')
                    ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
                    ->orderBy('advisor_id', 'ASC');
        if (!empty($CDBID)) {
            $query->where('tqr.id', '=', $CDBID);
        }
        if (!empty($email)) {
            $query->where('tqr.email', '=', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('tqr.mobile_no', '=', $mobile_no);
        }
        return $query;
    }
    public function getLeadsForAssignment()
    {
        return TravelQuote::orderBy('created_at', 'desc')->get();
    }

    public function getGridData($searchProperties, $request)
    {
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $this->query->where($this->getQuerySuffix($item) . '.' . $item, $request[$item]);
                }
            }
        }
        return $this->query;
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'travel_cover_for':
                return 'tcf';
                break;
            case 'region':
                return  'r';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            case 'nationality':
                return  'n';
                break;
            default:
                return  'tqr';
                break;
        }
    }

    public function getEntity($id)
    {
        return $this->query->where('tqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return TravelQuote::where('uuid', $id)->first();
    }

    public function updateTravelQuote(Request $request, $id)
    {
        $updateArray = [
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'details'=>$request->details,
            'travel_cover_for_id'=>$request->travel_cover_for_id,
            'nationality_id'=>$request->nationality_id,
            'days_cover_for'=>$request->days_cover_for,
            'destination'=>$request->destination,
            'region_cover_for_id'=>$request->region_cover_for_id,
            'details'=>$request->details,
        ];
        if(!Auth::user()->hasRole('TRAVEL_ADVISOR')){
            $updateArray['email'] = $request->email;
            $updateArray['mobile_no'] = $request->mobile_no;
        }
        TravelQuote::where('uuid',$id)->update($updateArray);
        if (isset($request->return_to_view))
            return redirect("quote/travel/" . $id)->with('success', 'Travel Quote has been updated');
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "code" => "input|title",
            "created_at" => "input|date|title",
            "updated_at" => "input|date|title",
            "mobile_no" => "input|number|title|required",
            "days_cover_for" => "input|number|title|required",
            "destination" => "input|text|required",
            "nationality_id" => "select|title|required",
            "region_cover_for_id" => "select|title|required",
            "travel_cover_for_id" => "select|title|required",
            "advisor_id" => "select|title|required",
            "quote_status_id" => "select|title",
            "details" => "textarea|text|required"
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'days_cover_for':
                $title = "How many days would you like cover for?";
                break;
            case 'advisor_id':
                $title = "Advisor";
                break;
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            case 'code':
                $title = "CDB ID";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
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

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,created_at,id,code,advisor_id,updated_at,quote_status_id",
            "list" => "email,mobile_no,region_cover_for_id,travel_cover_for_id,details,nationality_id,destination,days_cover_for",
            "update" => 'created_at,id,code,advisor_id,updated_at,quote_status_id'
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id','advisor_id','created_at'];
    }
}
