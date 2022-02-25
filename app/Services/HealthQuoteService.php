<?php

namespace App\Services;

use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use Illuminate\Http\Request;
use DB;
use Auth;
use \Carbon\Carbon;
use Hidehalo\Nanoid\Client;
use Config;

class HealthQuoteService extends BaseService
{
    protected $query;
    public function __construct()
    {
        $this->query = DB::table('health_quote_request as hqr')->select(
            'hqr.id',
            'hqr.uuid',
            'hqr.code',
            'hqr.first_name',
            'hqr.updated_at',
            'hqr.created_at',
            'hqr.last_name',
            'hqr.email',
            'hqr.mobile_no',
            'hqr.preference',
            'hqr.details',
            'hqr.source',
            'hqr.dob',
            'hqr.has_dental',
            'hqr.health_team_type',
            'hqr.has_home',
            'hqr.premium',
            'hqr.has_worldwide_cover',
            'hqr.marital_status_id',
            'ms.TEXT AS marital_status_id_text',
            'hqr.cover_for_id',
            'hcf.TEXT AS cover_for_id_text',
            'hqr.nationality_id',
            'n.TEXT AS nationality_id_text',
            'hqr.emirate_of_your_visa_id',
            'hqr.quote_status_id',
            'qs.text as quote_status_id_text',
            'e.TEXT AS emirate_of_your_visa_id_text',
            'hqr.advisor_id',
            'u.name as advisor_id_text',
            'hqrd.next_followup_date',
            'hqrd.notes',
            'ls.text as lost_reason',
        )
            ->leftJoin('marital_status as ms', 'ms.id', '=', 'hqr.marital_status_id')
            ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'hqrd.lost_reason_id')
            ->leftJoin('health_cover_for as hcf', 'hcf.id', '=', 'hqr.cover_for_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'hqr.nationality_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'hqr.emirate_of_your_visa_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
            ->where('qs.text', '!=', 'Fake');
    }

    public function getEntity($id)
    {
        return $this->query->where('hqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return HealthQuote::where('id', $id)->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = HealthQuoteRequestDetail::where('health_quote_request_id', $id)->first();
        $lostId = 0;
        if (!is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }
        return $lostId;
    }

    public function getDetailEntity($id)
    {
        $entity = HealthQuoteRequestDetail::where('health_quote_request_id', $id)->first();
        if (!$entity) {
            HealthQuoteRequestDetail::create([
                'health_quote_request_id' => $id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        return HealthQuoteRequestDetail::where('health_quote_request_id', $id)->first();
    }

    public function getLeadsForAssignment()
    {
        return HealthQuote::orderBy('created_at', 'desc')->get();
    }
    public function saveHealthQuote(Request $request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "details" => $request->details,
            "mobileNo" => $request->mobile_no,
            "preference" => $request->preference,
            "source" => $sourceName,
            "maritalStatusId" => $request->marital_status_id,
            "premium" => $request->premium,
            "referenceUrl" => $appUrl,
            "dob" => $request->dob,
            "coverForId" => $request->cover_for_id,
            "nationalityId" => $request->nationality_id,
            "hasDental" => $request->has_dental == 'on' ? true : false,
            "hasWorldwideCover" => $request->has_worldwide_cover == 'on' ?  true : false,
            "hasHome" => $request->has_home == 'on' ? true : false,
            "emirateOfYourVisaId" => $request->emirate_of_your_visa_id,
        );
        if (!Auth::user()->hasRole("ADMIN")) $dataArr['advisorId'] = Auth::user()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-health-quote', $dataArr);
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_start'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->next_followup_date) && $request->next_followup_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['next_followup_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['next_followup_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqr.created_at', [$dateFrom, $dateTo]);
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
                    $column = "hqr.created_at";
                }
                if ($column == 7) {
                    $column = "hqr.updated_at";
                }
                if ($column == 8) {
                    $column = "hqrd.next_followup_date";
                }
            } else {
                if ($column == 5) {
                    $column = "hqr.created_at";
                }
                if ($column == 6) {
                    $column = "hqr.updated_at";
                }
                if ($column == 7) {
                    $column = "hqrd.next_followup_date";
                }
            }
            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('hqr.created_at', 'DESC');
        }
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'marital_status_id':
                return 'ms';
                break;
            case 'health_cover_for':
                return 'hcf';
                break;
            case 'nationality':
                return 'n';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            case 'emirates':
                return 'e';
                break;
            case 'advisor':
                return 'u';
                break;
            default:
                return 'hqr';
                break;
        }
    }

    public function updateHealthQuote(Request $request, $id)
    {
        $healthQuote = HealthQuote::where('uuid', $id)->first();
        $healthQuote->first_name = $request->first_name;
        $healthQuote->last_name = $request->last_name;
        $healthQuote->details = $request->details;
        $healthQuote->preference = $request->preference;
        $healthQuote->source = $request->source;
        $healthQuote->marital_status_id = $request->marital_status_id;
        $healthQuote->dob = $request->dob;
        $healthQuote->cover_for_id = $request->cover_for_id;
        $healthQuote->nationality_id = $request->nationality_id;
        $healthQuote->has_dental = $request->has_dental == 'on' ? true : false;
        $healthQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? true : false;
        $healthQuote->has_home = $request->has_home == 'on' ? true : false;
        $healthQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $healthQuote->premium = $request->premium;
        $healthQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/health/" . $id)->with('success', 'Health Quote has been updated');
    }

    public function getHealthLeadsForAdvisor($request)
    {
        $query = DB::table('health_quote_request as hqr')
            ->select(
                'hqr.id',
                'hqr.uuid',
                'hqr.code',
                DB::raw("CONCAT_WS(' ',hqr.first_name,hqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'hqr.created_at as createdAt',
                'hqr.quote_status_id',
                'hqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'hqr.updated_at',
                'hqr.source as leadSource',
                'hqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqrd.advisor_assigned_by_id')
            ->where('qs.text', '!=', 'Fake')
            ->where('hqr.advisor_id', Auth::user()->id);

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 3) {
                $column = "hqr.created_at";
            }
            if ($column == 4) {
                $column = "hqrd.advisor_assigned_date";
            }
            if ($column == 7) {
                $column = "hqrd.next_followup_date";
            }
            $query->orderBy($column, $direction);
        }
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->nfdSart)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->nfdEnd)->endOfDay()->toDateTimeString();
            $query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('hqr.code', $request->cdbId);
        }
        if (isset($request->email) && $request->email != '') {
            $query->where('hqr.email', $request->email);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('hqr.quote_status_id', $request->leadStatus);
        }
        return $query;
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('health_quote_request as hqr')
            ->select(
                'hqr.id',
                'hqr.uuid',
                'hqr.first_name',
                'hqr.code',
                'hqr.last_name',
                'hqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Health' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status'
            )
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->orderBy('advisor_id', 'ASC');
        if (!empty($CDBID)) {
            $query->where('hqr.id', '=', $CDBID);
        }
        if (!empty($email)) {
            $query->where('hqr.email', '=', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('hqr.mobile_no', '=', $mobile_no);
        }
        return $query;
    }

    public function updateChildRecord($id)
    {
        $childRecord = HealthQuoteRequestDetail::where('health_quote_request_id', $id)->first();
        if (!empty($childRecord)) {
            $childRecord->advisor_assigned_by_id = Auth::user()->id;
            $childRecord->advisor_assigned_date = Carbon::now();
            $childRecord->save();
        }
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
            "dob" => "input|date|title|required",
            "next_followup_date" => "input|date|title|range",
            "lost_reason" => "input|text",
            "premium" => "input|number|required",
            "preference" => "input|text",
            "details" => "input|text",
            "source" => "input|text|title",
            "marital_status_id" => "select|title|required",
            "cover_for_id" => "select|title|required",
            "nationality_id" => "select|title|required",
            "health_team_type" => "|static|default:All|All,RM-NB,RM-Speed,EBP,No-Type",
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
            case 'code':
                $title = "CDB ID";
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
                $title = "Assigned To";
                break;
            case 'source':
                $title = "Source";
                break;
            case 'emirate_of_your_visa_id':
                $title = "Emirate of your visa";
                break;
            case 'dob':
                $title = "Date of Birth";
                break;
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            case 'health_team_type':
                $title = "Health Team Type";
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
            "create" => "created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason",
            "list" => "email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id",
            "update" => "created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason",
            "show" => "id,health_team_type,next_followup_date",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'health_team_type', 'next_followup_date'];
    }

    public function convertLeadToGM($lead)
    {
        $businessLead = new BusinessQuote();
        $businessLead->first_name = $lead->first_name;
        $businessLead->last_name = $lead->last_name;
        $businessLead->email = $lead->email;
        $businessLead->mobile_no = $lead->mobile_no;
        $businessLead->quote_status_id = $lead->quote_status_id;
        $businessLead->business_type_of_insurance_id = BusinessInsuranceType::where('text', '=', 'Group Medical')->first()->id;
        $businessLead->created_at = $lead->created_at;
        $businessLead->updated_at = $lead->updated_at;
        $businessLead->dob = $lead->dob;
        $businessLead->brief_details = $lead->details;
        $businessLead->source = $lead->source;
        $uuid = strtoupper($this->generateUUID());
        $businessLead->uuid = $uuid;
        $businessLead->code = 'BUS-' . $uuid;
        $businessLead->customer_id = $lead->customer_id;
        $businessLead->save();
        HealthQuote::find($lead->id)->delete();
    }

    public function generateUUID()
    {
        $client = new Client();
        $alphabets = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $nanoId = $client->formattedId($alphabets, 8);
        return $nanoId;
    }
}
