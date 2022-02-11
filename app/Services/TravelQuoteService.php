<?php

namespace App\Services;

use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use Illuminate\Http\Request;
use DB;
use Auth;
use \Carbon\Carbon;
use Config;

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
            'tqr.premium',
            'tqr.source',
            'tqr.nationality_id',
            'n.TEXT AS nationality_id_text',
            'qs.id as quote_status_id',
            'qs.text as quote_status_id_text',
            'u.id as advisor_id',
            'u.name as advisor_id_text',
            'tqr.region_cover_for_id',
            'r.TEXT AS region_cover_for_id_text',
            'tqrd.next_followup_date',
            'ls.text as lost_reason',
            'tqrd.notes'
        )
            ->leftJoin('travel_cover_for as tcf', 'tcf.id', '=', 'tqr.travel_cover_for_id')
            ->leftJoin('travel_quote_request_detail as tqrd', 'tqr.id', '=', 'tqrd.travel_quote_request_id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'tqrd.lost_reason_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'tqr.nationality_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'tqr.advisor_id')
            ->leftJoin('region as r', 'r.id', '=', 'tqr.region_cover_for_id')
            ->where('qs.text', '!=', 'Fake');
    }

    public function saveTravelQuote(Request $request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "details" => $request->details,
            "mobileNo" => $request->mobile_no,
            "travelCoverForId" => $request->travel_cover_for_id,
            "premium" => $request->premium,
            "nationalityId" => $request->nationality_id,
            "daysCoverFor" => $request->days_cover_for,
            "destination" => $request->destination,
            "regionCoverForId" => $request->region_cover_for_i,
            "source" => $sourceName,
            "referenceUrl" => $appUrl,
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
                'tqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('travel_quote_request_detail as tqrd', 'tqrd.travel_quote_request_id', '=', 'tqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'tqrd.advisor_assigned_by_id')
            ->where('qs.text', '!=', 'Fake')
            ->where('tqr.advisor_id', Auth::user()->id);

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 3) {
                $column = "tqr.created_at";
            }
            if ($column == 4) {
                $column = "tqrd.advisor_assigned_date";
            }
            if ($column == 7) {
                $column = "tqrd.next_followup_date";
            }
            $query->orderBy($column, $direction);
        }
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('tqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->nfdSart)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->nfdEnd)->endOfDay()->toDateTimeString();
            $query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('tqr.code', $request->cdbId);
        }
        if (isset($request->email) && $request->email != '') {
            $query->where('tqr.email', $request->email);
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
            if (isset($request->next_followup_date) && $request->next_followup_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['next_followup_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['next_followup_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('tqrd.next_followup_date', [$dateFrom, $dateTo]);
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
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
            $isAdmin = Auth::user()->hasRole("ADMIN");
            if ($isAdmin || $isManagerORDeputy == "1") {
                if ($column == 6) {
                    $column = "tqr.created_at";
                }
                if ($column == 7) {
                    $column = "tqr.updated_at";
                }
                if ($column == 8) {
                    $column = "tqrd.next_followup_date";
                }
            } else {
                if ($column == 5) {
                    $column = "tqr.created_at";
                }
                if ($column == 6) {
                    $column = "tqr.updated_at";
                }
                if ($column == 7) {
                    $column = "tqrd.next_followup_date";
                }
            }
            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('tqr.created_at', 'DESC');
        }
    }

    public function updateChildRecord($id)
    {
        $childRecord = TravelQuoteRequestDetail::where('travel_quote_request_id', $id)->first();
        if (!empty($childRecord)) {
            $childRecord->advisor_assigned_by_id = Auth::user()->id;
            $childRecord->advisor_assigned_date = Carbon::now();
            $childRecord->save();
        }
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

    public function getSelectedLostReason($id)
    {
        $entity = TravelQuoteRequestDetail::where('travel_quote_request_id', $id)->first();
        $lostId = 0;
        if (!is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }
        return $lostId;
    }

    public function getDetailEntity($id)
    {
        $entity = TravelQuoteRequestDetail::where('travel_quote_request_id', $id)->first();
        if (!$entity) {
            TravelQuoteRequestDetail::create([
                'travel_quote_request_id' => $id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        return TravelQuoteRequestDetail::where('travel_quote_request_id', $id)->first();
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
            'premium' => $request->premium,
            'destination' => $request->destination,
            'region_cover_for_id' => $request->region_cover_for_id,
            'details' => $request->details,
        ];
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
            "advisor_id" => "select|title",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "next_followup_date" => "input|date|title|range",
            "lost_reason" => "input|text",
            "source" => "input|text",
            "premium" => "input|number|required",
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
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason",
            "list" => "email,mobile_no,region_cover_for_id,travel_cover_for_id,details,nationality_id,destination,days_cover_for",
            "update" => 'created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason',
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'next_followup_date'];
    }

    public function getQuotePlans($id)
    {
        $quoteUuId = TravelQuote::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = Config::get('constants.KEN_API_ENDPOINT') . '/get-travel-quote-plans';
        $plansApiToken = Config::get('constants.KEN_API_TOKEN');
        $plansApiTimeout = Config::get('constants.KEN_API_TIMEOUT');
        $plansApiUserName = Config::get('constants.KEN_API_USER');
        $plansApiPassword = Config::get('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName . ":" . $plansApiPassword);

        $plansDataArr = array(
            "quoteUID" => $quoteUuId,
            "lang" => "en",
        );

        $client = new \GuzzleHttp\Client();

        try {
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json', 'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic ' . $authBasic
                    ],
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

            if (isset($response->message)) {
                $responseBodyAsString = $response->message;
            } else if (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } else {
                $responseBodyAsString = $response->msg;
            }

            return $responseBodyAsString;
        }
    }
}
