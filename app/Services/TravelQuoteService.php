<?php

namespace App\Services;

use App\Models\TravelQuote;
use Illuminate\Http\Request;
use DB;
use Auth;
use \Carbon\Carbon;

class TravelQuoteService extends BaseService
{

    protected $query;
    public function __construct()
    {
        $this->query = DB::table('travel_quote_request as tqr')->select(
            'tqr.id',
            'tqr.uuid',
            'tqr.created_at',
            'tqr.updated_at',
            'tqr.code',
            'tqr.days_cover_for',
            'tqr.details',
            'tqr.destination',
            'tqr.travel_cover_for_id',
            'tcf.TEXT AS travel_cover_for_id_text',
            'tqr.first_name',
            'tqr.last_name',
            'tqr.email',
            'tqr.mobile_no',
            'tqr.nationality_id',
            'n.TEXT AS nationality_id_text',
            'qs.id as quote_status_id',
            'qs.text as quote_status_id_text',
            'u.id as advisor_id',
            'u.name as advisor_id_text',
            'tqr.region_cover_for_id',
            'r.TEXT AS region_cover_for_id_text',
            'tqrd.next_followup_date',
            'tqrd.notes'
        )
            ->leftJoin('travel_cover_for as tcf', 'tcf.id', '=', 'tqr.travel_cover_for_id')
            ->leftJoin('travel_quote_request_detail as tqrd', 'tqr.id', '=', 'tqrd.travel_quote_request_id')
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
        if (Auth::user()->hasRole("TRAVEL_ADVISOR")) {
            $dataArr['advisorId'] = Auth::user()->id;
        }
        return CapiRequestService::sendCAPIRequest('/api/v1-save-travel-quote', $dataArr);
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('travel_quote_request as tqr')
            ->select(
                'tqr.id',
                'tqr.uuid',
                'tqr.first_name',
                'tqr.last_name',
                'tqr.code',
                'tqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Travel' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status'
            )
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

    public function getTravelLeadsForAdvisor($request)
    {
        $query = DB::table('travel_quote_request as tqr')
            ->select(
                'tqr.id',
                'tqr.uuid',
                'tqr.code',
                DB::raw("CONCAT_WS(' ',tqr.first_name,tqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'tqr.created_at as createdAt',
                'tqr.quote_status_id',
                'tqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'tqr.updated_at',
                'tqr.source as leadSource',
            )
            ->leftJoin('travel_quote_request_detail as tqrd', 'tqrd.travel_quote_request_id', '=', 'tqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'tqrd.advisor_assigned_by_id')
            ->where('tqr.advisor_id', Auth::user()->id);
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('tqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('tqr.code', $request->cdbId);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('tqr.quote_status_id', $request->leadStatus);
        }
        return $query;
    }

    public function getGridData($searchProperties, $request)
    {
        if ($request->ajax()) {
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_start'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('tqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('tqr.created_at', [$dateFrom, $dateTo]);
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else {
                        $this->query->where($this->getQuerySuffix($item) . '.' . $item, $request[$item]);
                    }
                }
            }
        }
        return $this->query->orderBy('tqr.advisor_id', 'ASC');
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
        return TravelQuote::where('id', $id)->first();
    }

    public function updateTravelQuote(Request $request, $id)
    {
        $updateArray = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'details' => $request->details,
            'travel_cover_for_id' => $request->travel_cover_for_id,
            'nationality_id' => $request->nationality_id,
            'days_cover_for' => $request->days_cover_for,
            'destination' => $request->destination,
            'region_cover_for_id' => $request->region_cover_for_id,
            'details' => $request->details,
        ];
        if (!Auth::user()->hasRole('TRAVEL_ADVISOR')) {
            $updateArray['email'] = $request->email;
            $updateArray['mobile_no'] = $request->mobile_no;
        }
        TravelQuote::where('uuid', $id)->update($updateArray);
        if (isset($request->return_to_view))
            return redirect("quote/travel/" . $id)->with('success', 'Travel Quote has been updated');
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "code" => "input|title",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "quote_status_id" => "select|title",
            "advisor_id" => "select|title|required",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "days_cover_for" => "input|number|title|required",
            "destination" => "input|text|required",
            "nationality_id" => "select|title|required",
            "region_cover_for_id" => "select|title|required",
            "travel_cover_for_id" => "select|title|required",
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
                $title = "Assigned To";
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
            "update" => 'created_at,id,code,advisor_id,updated_at,quote_status_id',
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at'];
    }
}
