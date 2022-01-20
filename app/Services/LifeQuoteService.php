<?php

namespace App\Services;

use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use Illuminate\Http\Request;
use DB;
use Auth;
use \Carbon\Carbon;
use Config;

class LifeQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('life_quote_request as lqr')
            ->select(
                'lqr.id',
                'lqr.uuid',
                'lqr.code',
                'lqr.updated_at',
                'lqr.created_at',
                'lqr.first_name',
                'lqr.last_name',
                'lqr.email',
                'lqr.mobile_no',
                'lqr.gender',
                'lqr.dob',
                'lqr.is_smoker',
                'lqr.others_info',
                'lqr.sum_insured_value',
                'lqr.source',
                'lqr.premium',
                'lqr.sum_insured_currency_id',
                'ct.TEXT AS sum_insured_currency_id_text',
                'lqr.marital_status_id',
                'ms.TEXT AS marital_status_id_text',
                'lqr.purpose_of_insurance_id',
                'lip.TEXT AS purpose_of_insurance_id_text',
                'lqr.children_id',
                'lc.TEXT AS children_id_text',
                'lqr.tenure_of_insurance_id',
                'lit.TEXT AS tenure_of_insurance_id_text',
                'lqr.quote_status_id',
                'qs.text as quote_status_id_text',
                'lqr.advisor_id',
                'u.name as advisor_id_text',
                'lqr.number_of_years_id',
                'liy.TEXT AS number_of_years_id_text',
                'lqr.nationality_id',
                'n.TEXT AS nationality_id_text',
                'lqrd.next_followup_date',
                'lqrd.notes',
                'ls.text as lost_reason',
            )
            ->leftJoin('life_quote_request_detail as lqrd', 'lqrd.life_quote_request_id', 'lqr.id')
            ->leftJoin('currency_type as ct', 'ct.id', '=', 'lqr.sum_insured_currency_id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'lqrd.lost_reason_id')
            ->leftJoin('marital_status as ms', 'ms.id', '=', 'lqr.marital_status_id')
            ->leftJoin('life_insurance_purpose as lip', 'lip.id', '=', 'lqr.purpose_of_insurance_id')
            ->leftJoin('life_children as lc', 'lc.id', '=', 'lqr.children_id')
            ->leftJoin('life_insurance_tenure as lit', 'lit.id', '=', 'lqr.tenure_of_insurance_id')
            ->leftJoin('life_number_of_year as liy', 'liy.id', '=', 'lqr.number_of_years_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'lqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'lqr.advisor_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'lqr.nationality_id');
    }
    public function saveLifeQuote(Request $request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "dob" => $request->dob,
            "sumInsuredValue" => $request->sum_insured_value,
            "sumInsuredCurrencyId" => $request->sum_insured_currency_id,
            "maritalStatusId" => $request->marital_status_id,
            "purposeOfInsuranceId" => $request->purpose_of_insurance_id,
            "childrenId" => $request->children_id,
            "premium" => $request->premium,
            "tenureOfInsuranceId" => $request->tenure_of_insurance_id,
            "numberOfYearsId" => $request->number_of_years_id,
            "isSmoker" => $request->is_smoker == 'Yes' ?  true : false,
            "gender" => $request->gender,
            "othersInfo" => $request->others_info,
            "source" => $sourceName,
            "referenceUrl" => $appUrl,
        );
        if (Auth::user()->hasRole("LIFE_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-life-quote', $dataArr);
    }

    public function getEntity($id)
    {
        return $this->query->where('lqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return LifeQuote::where('id', $id)->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = LifeQuoteRequestDetail::where('life_quote_request_id', $id)->first();
        $lostId = 0;
        if (!is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }
        return $lostId;
    }

    public function getDetailEntity($id)
    {
        $entity = LifeQuoteRequestDetail::where('life_quote_request_id', $id)->first();
        if (!$entity) {
            LifeQuoteRequestDetail::create([
                'life_quote_request_id' => $id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        return LifeQuoteRequestDetail::where('life_quote_request_id', $id)->first();
    }

    public function getLeadsForAssignment()
    {
        return LifeQuote::orderBy('created_at', 'desc')->get();
    }
    public function getGridData($searchProperties, $request)
    {
        if ($request->ajax()) {
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_start'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('lqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('lqr.created_at', [$dateFrom, $dateTo]);
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
        return $this->query->orderBy('lqr.created_at', 'DESC');
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'sum_insured_currency_id':
                return 'ct';
                break;
            case 'marital_status_id':
                return 'ms';
                break;
            case 'nationality_id':
                return 'n';
                break;
            case 'purpose_of_insurance_id':
                return 'lip';
                break;
            case 'children_id':
                return 'lc';
                break;
            case 'tenure_of_insurance_id':
                return 'lit';
                break;
            case 'number_of_years_id':
                return 'liy';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            default:
                return 'lqr';
                break;
        }
    }

    public function updateLifeQuote(Request $request, $id)
    {
        $updateArray = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'details' => $request->details,
            'dob' => $request->dob,
            'premium' => $request->premium,
            'gender' => $request->gender == 'Male' ? 'M' : 'F',
            'is_smoker' => $request->is_smoker == 'Yes' ? '1' : '0',
        ];
        if (!Auth::user()->hasRole('LIFE_ADVISOR')) {
            $updateArray['email'] = $request->email;
            $updateArray['mobile_no'] = $request->mobile_no;
        }
        LifeQuote::where('uuid', $id)->update($updateArray);

        if (isset($request->return_to_view))
            return redirect("quote/life/" . $lifeQuote->id)->with('success', 'Life Quote has been updated');
    }

    public function getLifeLeadsForAdvisor($request)
    {
        $query = DB::table('life_quote_request as lqr')
            ->select(
                'lqr.id',
                'lqr.uuid',
                'lqr.code',
                DB::raw("CONCAT_WS(' ',lqr.first_name,lqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'lqr.created_at as createdAt',
                'lqr.quote_status_id',
                'lqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'lqr.updated_at',
                'lqr.source as leadSource',
            )
            ->leftJoin('life_quote_request_detail as lqrd', 'lqrd.life_quote_request_id', '=', 'lqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'lqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'lqrd.advisor_assigned_by_id')
            ->where('lqr.advisor_id', Auth::user()->id);
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('lqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('lqr.code', $request->cdbId);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('lqr.quote_status_id', $request->leadStatus);
        }
        return $query;
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('life_quote_request as lqr')
            ->select(
                'lqr.id',
                'lqr.uuid',
                'lqr.first_name',
                'lqr.last_name',
                'lqr.code',
                'lqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Life' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status',
                'lqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('life_quote_request_detail as lqrd', 'lqrd.life_quote_request_id', '=', 'lqr.id')
            ->leftJoin('users as u', 'u.id', '=', 'lqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'lqr.quote_status_id')
            ->orderBy('advisor_id', 'ASC');
        if (!empty($CDBID)) {
            $query->where('lqr.id', '=', $CDBID);
        }
        if (!empty($email)) {
            $query->where('lqr.email', '=', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('lqr.mobile_no', '=', $mobile_no);
        }
        return $query;
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
            "sum_insured_value" => "input|number|title||required",
            "next_followup_date" => "input|text",
            "source" => "input|text|required",
            "lost_reason" => "input|text",
            "premium" => "input|number|required",
            "sum_insured_currency_id" => "select|title|required",
            "purpose_of_insurance_id" => "select|title|required",
            "marital_status_id" => "select|title|required",
            "children_id" => "select|title|required",
            "tenure_of_insurance_id" => "select|title|required",
            "number_of_years_id" => "select|title|required",
            "gender" => "|static|required|Male,Female",
            "is_smoker" => "|static|title|required|Yes,No",
            "others_info" => "textarea",
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'code':
                $title = "CDB ID";
                break;
            case 'purpose_of_insurance_id':
                $title = "Purpose of Insurance";
                break;
            case 'children_id':
                $title = "Children";
                break;
            case 'tenure_of_insurance_id':
                $title = "Tenure Of Insurance";
                break;
            case 'number_of_years_id':
                $title = "No. of Years";
                break;
            case 'sum_insured_currency_id':
                $title = "Currency";
                break;
            case 'dob':
                $title = "Date Of Birth";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'is_smoker':
                $title = "Smoker";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'sum_insured_value':
                $title = "Sum Insured Value";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            case 'advisor_id':
                $title = "Assigned To";
                break;
            case 'marital_status_id':
                $title = "Marital Status";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source",
            "list" => "email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info",
            "update" => "id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source",
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at'];
    }
}
