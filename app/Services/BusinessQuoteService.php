<?php

namespace App\Services;

use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use Illuminate\Http\Request;
use DB;
use Config;
use Auth;
use \Carbon\Carbon;

class BusinessQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('business_quote_request as bqr')
            ->select(
                'bqr.id',
                'bqr.uuid',
                'bqr.code',
                'bqr.created_at',
                'bqr.updated_at',
                'bqr.first_name',
                'bqr.last_name',
                'bqr.email',
                'bqr.mobile_no',
                'bqr.company_name',
                'bqr.brief_details',
                'bqr.number_of_employees',
                'bqr.business_type_of_insurance_id',
                'bti.TEXT AS business_type_of_insurance_id_text',
                'bqr.advisor_id',
                'u.name as advisor_id_text',
                'bqr.quote_status_id',
                'qs.text as quote_status_id_text',
                'bqr.premium',
                'bqrd.next_followup_date',
                'bqrd.notes',
                'bqrd.transapp_code',
                'ls.text as lost_reason',
                'bqr.source',
            )
            ->leftJoin('business_type_of_insurance as bti', 'bti.id', '=', 'bqr.business_type_of_insurance_id')
            ->leftJoin('business_quote_request_detail as bqrd', 'bqrd.business_quote_request_id', '=', 'bqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'bqrd.lost_reason_id')
            ->leftJoin('users as u', 'u.id', '=', 'bqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'bqr.quote_status_id');
    }

    public function getEntity($id)
    {
        return $this->query->where('bqr.uuid', $id)->first();
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('business_quote_request as bqr')
            ->select(
                'bqr.id',
                'bqr.uuid',
                'bqr.code',
                'bqr.first_name',
                'bqr.last_name',
                'bqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Business' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status'
            )
            ->leftJoin('users as u', 'u.id', '=', 'bqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'bqr.quote_status_id')
            ->orderBy('advisor_id', 'ASC');
        if (!empty($CDBID)) {
            $query->where('bqr.id', '=', $CDBID);
        }
        if (!empty($email)) {
            $query->where('bqr.email', '=', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('bqr.mobile_no', '=', $mobile_no);
        }
        return $query;
    }

    public function getBusinessOverDueFollowups()
    {
        $query = DB::table('business_quote_request as bqr')
            ->select(
                'bqr.id',
                'bqr.uuid',
                'bqr.code',
                DB::raw("CONCAT_WS(' ',bqr.first_name,bqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'bqr.created_at as createdAt',
                'bqr.quote_status_id',
                'bqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'bqr.updated_at',
                'bqr.source as leadSource',
                'bqr.company_name',
                'bqr.premium',
                'bqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('business_quote_request_detail as bqrd', 'bqrd.business_quote_request_id', '=', 'bqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'bqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'bqrd.advisor_assigned_by_id')
            ->where('bqr.advisor_id', Auth::user()->id)
            ->where('bqrd.next_followup_date', '<', date('Y-m-d'))
            ->whereIn('qs.text', ['Followed Up','Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'])
            ->where('qs.text', '!=', 'Fake');
        return $query;
    }

    public function getBusinessLeadsForAdvisor($request)
    {
        $query = DB::table('business_quote_request as bqr')
            ->select(
                'bqr.id',
                'bqr.uuid',
                'bqr.code',
                DB::raw("CONCAT_WS(' ',bqr.first_name,bqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'bqr.created_at as createdAt',
                'bqr.quote_status_id',
                'bqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'bqr.updated_at',
                'bqr.source as leadSource',
                'bqr.company_name',
                'bqr.premium',
                'bqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('business_quote_request_detail as bqrd', 'bqrd.business_quote_request_id', '=', 'bqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'bqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'bqrd.advisor_assigned_by_id')
            ->where(function ($query) use ($request) {
                $query->where('bqrd.next_followup_date', '>', date('Y-m-d'));
                $query->orwhereNull('bqrd.next_followup_date');
            })
            ->where('bqr.advisor_id', Auth::user()->id)
            ->where('qs.text', '!=', 'Fake');
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('bqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->nfdSart)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->nfdEnd)->endOfDay()->toDateTimeString();
            $query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 3) {
                $column = "bqr.created_at";
            }
            if ($column == 4) {
                $column = "bqrd.advisor_assigned_date";
            }
            if ($column == 7) {
                $column = "bqrd.next_followup_date";
            }
            $query->orderBy($column, $direction);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('bqr.code', $request->cdbId);
        }
        if (isset($request->email) && $request->email != '') {
            $query->where('bqr.email', $request->email);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('bqr.quote_status_id', $request->leadStatus);
        }
        return $query;
    }

    public function getEntityPlain($id)
    {
        return BusinessQuote::where('id', $id)->first();
    }

    public function updateChildRecord($id)
    {
        $childRecord = BusinessQuoteRequestDetail::where('business_quote_request_id', $id)->first();
        if (!empty($childRecord)) {
            $childRecord->advisor_assigned_by_id = Auth::user()->id;
            $childRecord->advisor_assigned_date = Carbon::now();
            $childRecord->save();
        }
    }

    public function getDetailEntity($id)
    {
        $entity = BusinessQuoteRequestDetail::where('business_quote_request_id', $id)->first();
        if (!$entity) {
            BusinessQuoteRequestDetail::create([
                'business_quote_request_id' => $id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        return BusinessQuoteRequestDetail::where('business_quote_request_id', $id)->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = BusinessQuoteRequestDetail::where('business_quote_request_id', $id)->first();
        $lostId = 0;
        if (!is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }
        return $lostId;
    }

    public function getLeadsForAssignment()
    {
        return BusinessQuote::orderBy('created_at', 'desc')->get();
    }

    public function saveBusinessQuote(Request $request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "numberOfEmployees" => $request->number_of_employees,
            "mobileNo" => $request->mobile_no,
            "companyName" => $request->company_name,
            "briefDetails" => $request->brief_details,
            "premium" => $request->premium,
            "businessTypeOfInsuranceId" => $request->business_type_of_insurance_id,
            "source" => $sourceName,
            "referenceUrl" => $appUrl,
        );
        if (!Auth::user()->hasRole("ADMIN")) $dataArr['advisorId'] = Auth::user()->id;
        $response  = CapiRequestService::sendCAPIRequest('/api/v1-save-business-quote', $dataArr);
        return $response;
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
                $this->query->whereBetween('bqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->next_followup_date) && $request->next_followup_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['next_followup_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['next_followup_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('bqrd.next_followup_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('bqr.created_at', [$dateFrom, $dateTo]);
            }
            if(Auth::user()->isSpecificTeamAdvisor('Business') || Auth::user()->isSpecificTeamAdvisor('CorpLine') || Auth::user()->isSpecificTeamAdvisor('AMT')){
                // if user has advisor Role then fetch leads assigned to the user only
                $this->query->where('bqr.advisor_id', Auth::user()->id);	// fetch leads assigned to the user
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
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
            $isAdmin = Auth::user()->hasRole("ADMIN");
            if ($isAdmin || $isManagerORDeputy == "1") {
                if ($column == 10) {
                    $column = "bqr.created_at";
                }
                if ($column == 11) {
                    $column = "bqr.updated_at";
                }
                if ($column == 5) {
                    $column = "bqrd.next_followup_date";
                }
            } else {
                if ($column == 9) {
                    $column = "bqr.created_at";
                }
                if ($column == 10) {
                    $column = "bqr.updated_at";
                }
                if ($column == 4) {
                    $column = "bqrd.next_followup_date";
                }
            }
            return $this->query->where('bti.text', '!=', 'Group Medical')->orderBy($column, $direction);
        } else {
            return $this->query->where('bti.text', '!=', 'Group Medical')->orderBy('bqr.created_at', 'DESC');
        }
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'business_type_of_insurance':
                return 'bti';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            default:
                return 'bqr';
                break;
        }
    }

    public function updateBusinessQuote(Request $request, $id)
    {
        $businessQuote = BusinessQuote::where('uuid', $id)->first();
        if ($businessQuote) {
            $businessQuote->first_name = $request->first_name;
            $businessQuote->last_name = $request->last_name;
            $businessQuote->company_name = $request->company_name;
            $businessQuote->brief_details = $request->brief_details;
            $businessQuote->premium = $request->premium;
            $businessQuote->business_type_of_insurance_id = $request->business_type_of_insurance_id;
            $businessQuote->number_of_employees = $request->number_of_employees;
            if (isset($request->group_medical_type_id)) $businessQuote->group_medical_type_id = $request->group_medical_type_id;
            $businessQuote->save();

            if (isset($request->return_to_view))
                return redirect("quote/business/" . $businessQuote->id)->with('success', 'Business Quote has been updated');
        } else {
            return redirect("quote/business")->with('message', 'Business Quote not found');
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
            "company_name" => "input|text|required",
            "next_followup_date" => "input|date|title|range",
            "transapp_code" => "readonly|none",
            "source" => "input|text",
            "lost_reason" => "input|text",
            "advisor_id" => "select|title|multiple",
            "quote_status_id" => "select|title|multiple",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "premium" => "input|number|required",
            "number_of_employees" => "input|title|number",
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
            case 'number_of_employees':
                $title = "Number of Employees";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            case 'code':
                $title = "CDB ID";
                break;
            case 'advisor_id':
                $title = "Assigned To";
                break;
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,premium,source,transapp_code",
            "list" => "email,mobile_no,brief_details,dob",
            "update" => "id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,source,transapp_code",
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'company_name', 'business_type_of_insurance_id', 'next_followup_date'];
    }
}
