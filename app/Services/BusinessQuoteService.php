<?php

namespace App\Services;

use App\Models\BusinessQuote;
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
            )
            ->Join('business_type_of_insurance as bti', 'bti.id', '=', 'bqr.business_type_of_insurance_id')
            ->leftJoin('business_quote_request_detail as bqrd', 'bqrd.business_quote_request_id', '=', 'bqr.id')
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
            )
            ->leftJoin('business_quote_request_detail as bqrd', 'bqrd.business_quote_request_id', '=', 'bqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'bqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'bqrd.advisor_assigned_by_id')
            ->where('bqr.advisor_id', Auth::user()->id);
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('bqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('bqr.code', $request->cdbId);
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
        if (Auth::user()->hasRole("BUSINESS_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-business-quote', $dataArr);
    }

    public function getGridData($searchProperties, $request)
    {
        if ($request->ajax()) {
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_start'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('bqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('bqr.created_at', [$dateFrom, $dateTo]);
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
        return $this->query->where('bti.text', '!=', 'Group Medical')->orderBy('bqr.advisor_id', 'ASC');
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
        $businessQuote = BusinessQuote::find($id);
        $businessQuote->first_name = $request->first_name;
        $businessQuote->last_name = $request->last_name;
        if (!Auth::user()->hasRole('BUSINESS_ADVISOR')) {
            $businessQuote->email = $request->email;
            $businessQuote->mobile_no = $request->mobile_no;
        }
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
            "code" => "input|title",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "company_name" => "input|text|required",
            "advisor_id" => "select|title|required",
            "quote_status_id" => "select|title",
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
            "create" => "id,advisor_id,quote_status_id,code,premium",
            "list" => "email,mobile_no,brief_details,dob",
            "update" => "id,advisor_id,quote_status_id,code,premium",
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'company_name', 'business_type_of_insurance_id'];
    }
}
