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
                'bqr.business_type_of_insurance_id',
                'bti.TEXT AS business_type_of_insurance_id_text',
                'bqr.advisor_id',
                'u.name as advisor_id_text',
                'bqr.quote_status_id',
                'qs.text as quote_status_id_text'
            )
            ->Join('business_type_of_insurance as bti', 'bti.id', '=', 'bqr.business_type_of_insurance_id')
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
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('bqr.created_at', [$dateFrom, $dateTo]);
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    $this->query->where($this->getQuerySuffix($item) . '.' . $item, $request[$item]);
                }
            }
        }
        $this->query->orderBy('bqr.created_at', 'DESC');
        return $this->query;
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
            "quote_status_id" => "select|title",
            "advisor_id" => "select|title|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "company_name" => "input|text|required",
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
            "create" => "id,advisor_id,quote_status_id,code",
            "list" => "email,mobile_no,company_name,brief_details,business_type_of_insurance_id,dob",
            "update" => "id,advisor_id,quote_status_id,code",
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at'];
    }
}
