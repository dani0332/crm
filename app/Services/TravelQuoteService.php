<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Models\LeadStatus;
use App\Models\QuoteStatus;
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
            'tqr.previous_quote_id',
            'tqr.first_name',
            'tqr.last_name',
            'tqr.email',
            'tqr.mobile_no',
            'tqr.dob',
            'tqr.premium',
            'tqr.paid_at',
            'tqr.source',
            'tqr.policy_number',
            'tqr.nationality_id',
            'n.TEXT AS nationality_id_text',
            'qs.id as quote_status_id',
            'qs.text as quote_status_id_text',
            'u.id as advisor_id',
            'u.name as advisor_id_text',
            'tqr.payment_status_id',
            'ps.text AS payment_status_id_text',
            'tqr.plan_id',
            'tp.text AS plan_id_text',
            'tqr.region_cover_for_id',
            'r.TEXT AS region_cover_for_id_text',
            'tqrd.next_followup_date',
            'tqrd.transapp_code',
            'ls.text as lost_reason',
            'tqrd.notes',
            'tqr.currently_located_in_id',
            'cli.text as currently_located_in_id_text',
            'tqr.destination_id',
            'nationality.text as destination_id_text'
        )
            ->leftJoin('travel_cover_for as tcf', 'tcf.id', '=', 'tqr.travel_cover_for_id')
            ->leftJoin('travel_quote_request_detail as tqrd', 'tqr.id', '=', 'tqrd.travel_quote_request_id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'tqrd.lost_reason_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'tqr.nationality_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'tqr.advisor_id')
            ->leftJoin('region as r', 'r.id', '=', 'tqr.region_cover_for_id')
            ->leftJoin('currently_located_in as cli', 'cli.id', '=', 'tqr.currently_located_in_id')
            ->leftJoin('nationality', 'nationality.id', '=', 'tqr.destination_id')
            ->leftJoin('travel_plan as tp', 'tp.id', '=', 'tqr.plan_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'tqr.payment_status_id');
    }

    public function saveTravelQuote(Request $request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "mobileNo" => $request->mobile_no,
            "travelCoverForId" => $request->travel_cover_for_id,
            "premium" => $request->premium,
            "nationalityId" => $request->nationality_id,
            "daysCoverFor" => $request->days_cover_for,
            "destinationId" => $request->destination_id,
            "regionCoverForId" => $request->region_cover_for_id,
            "source" => $sourceName,
            "referenceUrl" => $appUrl,
            "currentlyLocatedInId" => $request->currently_located_in_id,
            "dob" => $request->dob
        );
        if (!Auth::user()->hasRole("ADMIN")) $dataArr['advisorId'] = Auth::user()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-travel-quote', $dataArr);
    }

    public function getTravelOverDueFollowups()
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
                'tqr.premium',
                'tqrd.next_followup_date as nextFollowupDate'
            )
            ->leftJoin('travel_quote_request_detail as tqrd', 'tqrd.travel_quote_request_id', '=', 'tqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'tqrd.advisor_assigned_by_id')
            ->where('tqrd.next_followup_date', '<', date('Y-m-d H:i:s'))
            ->whereIn('qs.text', ['Followed Up','Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'])
            ->where('tqr.advisor_id', Auth::user()->id);
            return $query;
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
                'tqr.updated_at as updatedAt',
                'tqr.premium as premium',
                'tqr.email as email',
                'tqr.mobile_no as mobile_no',
                'tqr.source as leadSource',
                'tqrd.next_followup_date as nextFollowupDate',
                'tqr.previous_quote_id'
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

        if (Auth::user()->isRenewalAdvisor()) {
            $query->whereNotNull('tqr.previous_quote_id');
        }
        return $query;
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            if (!isset($request->email) && $request->email == '') {
                $this->query->where('qs.text', '!=', 'Fake');
            }
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

            if (isset($request->code) && $request->code != '') {
                $this->query->where('tqr.code', $request->code);
            }
            if (isset($request->first_name) && $request->first_name != '') {
                $this->query->where('tqr.first_name', $request->first_name);
            }
            if (isset($request->last_name) && $request->last_name != '') {
                $this->query->where('tqr.last_name', $request->last_name);
            }
            if (isset($request->email) && $request->email != '') {
                $this->query->where('tqr.email', $request->email);
            }
            if (isset($request->mobile_no) && $request->mobile_no != '') {
                $this->query->where('tqr.mobile_no', $request->mobile_no);
            }
            if (isset($request->policy_number) && $request->policy_number != '') {
                $this->query->where('tqr.policy_number', $request->policy_number);
            }
            if(Auth::user()->isSpecificTeamAdvisor('Travel')){
                // if user has advisor Role then fetch leads assigned to the user only
                $this->query->where('tqr.advisor_id', Auth::user()->id);	// fetch leads assigned to the user
            }
            if (Auth::user()->isRenewalAdvisor()) {
                $this->query->whereNotNull('tqr.previous_quote_id');
                $this->query->where('tqr.advisor_id', Auth::user()->id);
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else if ($item == 'advisor_id' && is_array($request[$item]) && !empty($request[$item])) {
                        if($request[$item][0] == 'null')
                            $this->query->whereNull('advisor_id');
                        else
                            $this->query->whereIn('advisor_id', $request[$item]);
                    }
                    else if ($item == 'quote_status_id' && is_array($request[$item]) && !empty($request[$item])) {
                        $this->query->whereIn('quote_status_id', $request[$item]);
                    } else {
                        $this->query->where($this->getQuerySuffix($item) . '.' . $item, $request[$item]);
                    }
                }
            }
        }
       
        $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            
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
        $travelQuote = TravelQuote::where('uuid', $id)->first();
        $travelQuote->first_name = $request->first_name;
        $travelQuote->last_name = $request->last_name;
        $travelQuote->travel_cover_for_id = $request->travel_cover_for_id;
        $travelQuote->nationality_id = $request->nationality_id;
        $travelQuote->days_cover_for = $request->days_cover_for;
        $travelQuote->premium = $request->premium;
        $travelQuote->destination = $request->destination;
        $travelQuote->region_cover_for_id = $request->region_cover_for_id;
        $travelQuote->currently_located_in_id = $request->currently_located_in_id;
        $travelQuote->destination_id = $request->destination_id;
        $travelQuote->dob = $request->dob;
        $travelQuote->save();
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
            "quote_status_id" => "select|title|multiple",
            "advisor_id" => "select|title|multiple",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "dob" => "input|date|title",
            "next_followup_date" => "input|date|title|range",
            "transapp_code" => "readonly|none",
            "lost_reason" => "input|text",
            "source" => "input|text",
            "premium" => "input|number|required",
            "policy_number" => "input|text|required",
            "days_cover_for" => "input|number|title|required",
            "nationality_id" => "select|title|required",
            "destination_id" => "select|title|required",
            "region_cover_for_id" => "select|title|required",
            "travel_cover_for_id" => "select|title|required",
            "details" => "textarea|text|required",
            "currently_located_in_id" => "select|title|required",
            "previous_quote_id" => "readonly|title"
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
            case 'dob':
                $title = "Date of Birth";
                break;
            case 'region_cover_for_id':
                $title = "Which regions do you need cover for?";
                break;
            case 'nationality_id':
                $title = "Nationality";
                break;
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            case 'currently_located_in_id':
                $title = "Currently Located In ";
                break;
            case 'travel_cover_for_id':
                $title = "Who would you like cover for?";
                break;
            case 'destination_id':
                $title = "Destination";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            case 'previous_quote_id':
                $title = "Previous Quote Id";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "previous_quote_id,id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason,premium,source,transapp_code",
            "list" => "previous_quote_id,email,mobile_no,region_cover_for_id,travel_cover_for_id,details,nationality_id,days_cover_for",
            "update" => 'previous_quote_id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason,source,transapp_code',
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'next_followup_date'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number','premium'];
        $model->renewalSkipProperties = [
            "create" => "previous_quote_id,id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason,premium,source,transapp_code",
            "list" => "dob,previous_quote_id,email,mobile_no,region_cover_for_id,travel_cover_for_id,details,nationality_id,days_cover_for,next_followup_date,lost_reason,source,transapp_code,currently_located_in_id,destination_id",
            "update" => 'previous_quote_id,created_at,id,code,advisor_id,updated_at,quote_status_id,next_followup_date,lost_reason,source,transapp_code',
            "show" => "",
        ];
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
    public function getMembersDetail($id)
    {
        return DB::table("travel_quote_request_member_details")->where('travel_quote_request_id', $id)->get();
    }

    public function getDuplicateEntityByCode($code)
    {
        return TravelQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function createDuplicate($parentRecord)
    {
        $quote = new TravelQuote();
        $quote->parent_duplicate_quote_id = $parentRecord->code;
        $response = CapiRequestService::getUUID(QuoteTypeId::Travel);
        if($response) {
            $quote->uuid = $response->uuid;
            $quote->code = 'TRA-'. $response->uuid;
        }
        $quote->quote_status_id = QuoteStatus::where('text', 'New Lead')->first()->id;
        $quote->first_name = $parentRecord->first_name;
        $quote->last_name = $parentRecord->last_name;
        $quote->email = $parentRecord->email;
        $quote->advisor_id = Auth::user()->id;
        $quote->mobile_no = $parentRecord->mobile_no;
        $quote->save();
    }

    public function getLeadAuditHistory($id)
    {
        $audits = DB::table('car_quote_request as cqr')
        ->select(
            'a.created_at as ModifiedAt',
            DB::raw('(SELECT name from users where id = a.user_id) as ModifiedBy'),
            DB::raw("(SELECT TEXT FROM quote_status WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.quote_status_id'))) AS NewStatus"),
            DB::raw("(SELECT NAME FROM users WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.advisor_id'))) AS NewAdvisor"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.notes')) AS NewNotes")
        )
        ->join('travel_quote_request_detail as cqrd', 'cqrd.travel_quote_request_id', '=', 'cqr.id')
        ->join("audits as a",function($query){
            $query->on("a.auditable_id","=","cqr.id")
                ->orOn("a.auditable_id","=","cqrd.id");
        })
        ->where(function ($query) {
            $query->where('a.auditable_type', 'App\Models\TravelQuote')
            ->orWhere('a.auditable_type', 'App\Models\TravelQuoteRequestDetail');
        })
        ->where(function ($query) {
            $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.quote_status_id')"))
            ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.notes')"))
            ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.advisor_id')"));
        })
        ->where('cqr.id', $id)
        ->where('a.new_values', 'like', '%%')
        ->orderBy('a.created_at', 'DESC')->get();
        return $audits;
    }
}
