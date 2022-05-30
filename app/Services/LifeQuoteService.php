<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Models\QuoteStatus;
use Illuminate\Http\Request;
use DB;
use Auth;
use \Carbon\Carbon;
use Config;
use App\Traits\GetUserTree;
use App\Traits\CustomerAdditionalInfo as CustomerAdditionalInfoTrait;
use App\Enums\quoteTypeCode;
use App\Enums\DatabaseColumnsString;
class LifeQuoteService extends BaseService
{
    protected $query;
    use GetUserTree;
    use CustomerAdditionalInfoTrait;
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
                'lqr.policy_number',
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
                'lqrd.transapp_code',
                'lqrd.notes',
                'ls.text as lost_reason',
                'lqr.previous_quote_id',
                'lqr.renewal_batch',
                'lqr.previous_quote_policy_number',
                'lqr.renewal_expiry_date',
                'lqr.device',
                'lqr.previous_policy_expiry_date',
                'lqr.previous_quote_policy_premium'
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
        if (!Auth::user()->hasRole("ADMIN")) $dataArr['advisorId'] = Auth::user()->id;
        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-life-quote', $dataArr);
        if(isset($response->quoteUID))
            return $this->createUpdateCustomerInfo($request, $request->email, $response->quoteUID, quoteTypeCode::LifeQuote);
        else
            return $response;
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
    public function getGridData($model, $request)
    {
        $searchProperties = [];
        $isRenewalUser = Auth::user()->isRenewalUser();
        $isRenewalAdvisor = Auth::user()->isRenewalAdvisor();
        $isRenewalManager = Auth::user()->isRenewalManager();
        $isNewManager = Auth::user()->isNewBusinessManager();
        $isNewAdvisor = Auth::user()->isNewBusinessAdvisor();
        if ($isRenewalUser || $isRenewalManager || $isRenewalAdvisor) {
            $searchProperties = $model->renewalSearchProperties;
        }
        else if ($isNewManager || $isNewAdvisor) {
            $searchProperties = $model->newBusinessSearchProperties;
        } else {
            $searchProperties = $model->searchProperties;
        }
        if ($request->ajax()) {
            if (!isset($request->email) && $request->email == '') {
                $this->query->where('qs.text', '!=', 'Fake');
            }
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
            if (isset($request->next_followup_date) && $request->next_followup_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['next_followup_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['next_followup_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('lqrd.next_followup_date', [$dateFrom, $dateTo]);
            }
            if(Auth::user()->isSpecificTeamAdvisor('Life')){
                // if user has advisor Role then fetch leads assigned to the user only
                $this->query->where('lqr.advisor_id', Auth::user()->id);	// fetch leads assigned to the user
            }
            if (isset($request->code) && $request->code != '') {
                $this->query->where('lqr.code', $request->code);
            }
            if (isset($request->first_name) && $request->first_name != '') {
                $this->query->where('lqr.first_name', $request->first_name);
            }
            if (isset($request->last_name) && $request->last_name != '') {
                $this->query->where('lqr.last_name', $request->last_name);
            }
            if (isset($request->email) && $request->email != '') {
                $this->query->where('lqr.email', $request->email);
            }
            if (isset($request->mobile_no) && $request->mobile_no != '') {
                $this->query->where('lqr.mobile_no', $request->mobile_no);
            }
            if (isset($request->policy_number) && $request->policy_number != '') {
                $this->query->where('lqr.policy_number', $request->policy_number);
            }
            if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
                $this->query->where('lqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            }
            if (isset($request->renewal_batch) && $request->renewal_batch != '') {
                $this->query->where('lqr.renewal_batch', $request->renewal_batch);
            }
            if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('lqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
                $this->query->where('lqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
            }
            if (Auth::user()->isRenewalAdvisor()) {
                $this->query->whereNotNull('lqr.previous_quote_id');
                $this->query->where('lqr.advisor_id', Auth::user()->id);
            }
            if (Auth::user()->isRenewalManager()) {
                $ids = $this->walkTree(Auth::user()->id);
                $this->query->whereIn('lqr.advisor_id', $ids);
                $this->query->whereNotNull('lqr.previous_quote_id');
            }
            if (Auth::user()->isNewBusinessManager()) {
                $ids = $this->walkTree(Auth::user()->id);
                $this->query->whereIn('lqr.advisor_id', $ids);
                $this->query->whereNull('lqr.previous_quote_id');
            }
            if (Auth::user()->isNewBusinessAdvisor()) {
                $ids = $this->walkTree(Auth::user()->id);
                $this->query->whereIn('lqr.advisor_id', $ids);
                $this->query->whereNull('lqr.previous_quote_id');
            }
            if (isset($request->is_renewal) && $request->is_renewal != '') {
                if($request->is_renewal == quoteTypeCode::yesText)
                    $this->query->whereNotNull('lqr.previous_quote_id');
                if($request->is_renewal == quoteTypeCode::noText)
                    $this->query->whereNull('lqr.previous_quote_id');
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
                    else if ($item == DatabaseColumnsString::QUOTE_Status_ID && is_array($request[$item]) && !empty($request[$item])) {
                        $this->query->whereIn('quote_status_id', $request[$item]);
                    } else {
                        $skipped = array('is_renewal','previous_policy_expiry_date');
                        if(in_array($item, $skipped)){
                            continue;
                        }
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
                    $column = "lqr.created_at";
                }
                if ($column == 7) {
                    $column = "lqr.updated_at";
                }
                if ($column == 8) {
                    $column = "lqrd.next_followup_date";
                }
            } else {
                if ($column == 5) {
                    $column = "lqr.created_at";
                }
                if ($column == 6) {
                    $column = "lqr.updated_at";
                }
                if ($column == 7) {
                    $column = "lqrd.next_followup_date";
                }
            }
            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('lqr.created_at', 'DESC');
        }
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
            case 'previous_quote_id':
                $title = "Previous Quote ID";
                break;    
            default:
                return 'lqr';
                break;
        }
    }

    public function updateLifeQuote(Request $request, $id)
    {
        $lifeQuote = LifeQuote::where('uuid', $id)->first();
        $lifeQuote->first_name = $request->first_name;
        $lifeQuote->last_name = $request->last_name;
        $lifeQuote->dob = $request->dob;
        $lifeQuote->sum_insured_value = $request->sum_insured_value;
        $lifeQuote->sum_insured_currency_id = $request->sum_insured_currency_id;
        $lifeQuote->marital_status_id = $request->marital_status_id;
        $lifeQuote->purpose_of_insurance_id = $request->purpose_of_insurance_id;
        $lifeQuote->children_id = $request->children_id;
        $lifeQuote->premium = $request->premium;
        $lifeQuote->tenure_of_insurance_id = $request->tenure_of_insurance_id;
        $lifeQuote->number_of_years_id = $request->number_of_years_id;
        $lifeQuote->others_info = $request->others_info;
        $lifeQuote->save();

        $this->createUpdateCustomerInfo($request, $lifeQuote->email, $lifeQuote->uuid, quoteTypeCode::LifeQuote);
        if (isset($request->return_to_view))
            return redirect("quotes/life")->with('success', 'Life Quote has been updated');
    }

    public function getLifeOverDueFollowups()
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
                'lqr.premium',
                'lqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('life_quote_request_detail as lqrd', 'lqrd.life_quote_request_id', '=', 'lqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'lqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'lqrd.advisor_assigned_by_id')
            ->where('lqrd.next_followup_date', '<', date('Y-m-d H:i:s'))
            ->whereIn('qs.text', ['Followed Up','Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'])
            ->where('lqr.advisor_id', Auth::user()->id);
            return $query;
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
                'lqr.updated_at as updatedAt',
                'lqr.source as leadSource',
                'lqr.policy_number as policy_number',
                'lqr.email',
                'lqr.mobile_no',
                'lqrd.next_followup_date as nextFollowupDate',
                'lqr.previous_quote_id',
                'ps.text as paymentStatus',
                'lqr.renewal_batch',
                'lqr.previous_quote_policy_number as previous_policy_number',
                'lqr.previous_policy_expiry_date',
                'lqr.previous_quote_policy_premium'
            )
            ->leftJoin('life_quote_request_detail as lqrd', 'lqrd.life_quote_request_id', '=', 'lqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'lqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'lqrd.advisor_assigned_by_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'lqr.payment_status_id')
            ->where('qs.text', '!=', 'Fake')
            ->where('lqr.advisor_id', Auth::user()->id);

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 3) {
                $column = "lqr.created_at";
            }
            if ($column == 4) {
                $column = "lqrd.advisor_assigned_date";
            }
            if ($column == 7) {
                $column = "lqrd.next_followup_date";
            }
            $query->orderBy($column, $direction);
        }
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('lqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->nfdSart)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->nfdEnd)->endOfDay()->toDateTimeString();
            $query->whereBetween('lqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('lqr.code', $request->cdbId);
        }
        if (isset($request->email) && $request->email != '') {
            $query->where('lqr.email', $request->email);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('lqr.quote_status_id', $request->leadStatus);
        }
        if (isset($request->paymentStatus)) {
            $query->where('lqr.payment_status_id', $request->paymentStatus);
        }
        if (Auth::user()->isRenewalAdvisor()) {
            $query->whereNotNull('lqr.previous_quote_id');
        }
        if (Auth::user()->isNewBusinessAdvisor()) {
            $query->whereNull('lqr.previous_quote_id');
        }
        if (isset($request->paymentStatus) && $request->paymentStatus != '') {
            $query->where('lqr.payment_status_id', $request->paymentStatus);
        }
        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $query->where('lqr.renewal_batch', $request->renewal_batch);
        }
        if (isset($request->previous_policy_number) && $request->previous_policy_number != '') {
            $query->where('lqr.previous_quote_policy_number', $request->previous_policy_number);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $query->where('lqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '' && $request->previous_policy_expiry_date_end != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $query->whereBetween('lqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
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

    public function updateChildRecord($id)
    {
        $childRecord = LifeQuoteRequestDetail::where('life_quote_request_id', $id)->first();
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
            "quote_status_id" => "select|title|multiple",
            "advisor_id" => "select|title|multiple",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "dob" => "input|date|title|required",
            "sum_insured_value" => "input|number|title|required",
            "next_followup_date" => "input|date|title|range",
            "transapp_code" => "readonly|none",
            "source" => "input|text",
            "lost_reason" => "input|text",
            "premium" => "input|number",
            "policy_number" => "input|text",
            "sum_insured_currency_id" => "select|title|required",
            "purpose_of_insurance_id" => "select|title|required",
            "marital_status_id" => "select|title|required",
            "children_id" => "select|title|required",
            "tenure_of_insurance_id" => "select|title|required",
            "number_of_years_id" => "select|title|required",
            "gender" => "|static|Male,Female",
            "is_smoker" => "|static|title|Yes,No",
            "others_info" => "textarea",
            "previous_quote_id" => "readonly|title",
            "is_renewal" => "|static|title|Yes,No",
            "renewal_expiry_date" => "input|date|title|range",
            "renewal_batch" => "input|none",
            "previous_quote_policy_number" => "input|title",
            "previous_policy_expiry_date" => "input|date|title|range",
            "previous_quote_policy_premium" => "input|title",
            "device" => "input|title"
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
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            case 'previous_quote_id':
                $title = "Previous Quote Id";
                break;
            case 'renewal_expiry_date':
                $title = "Expiry Date";
                break;
            case 'previous_quote_policy_number':
                $title = "Previous Policy Number";
                break;
            case 'previous_policy_expiry_date':
                $title = "Previous Policy Expiry Date";
                break;
            case 'previous_quote_policy_premium':
                $title = "Previous Policy Premium";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,premium,source,transapp_code",
            "list" => "previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info",
            "update" => "previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code",
            "show" => "is_renewal",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'next_followup_date','is_renewal'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'previous_quote_policy_number','previous_policy_expiry_date','renewal_batch','previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            "create" => "previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,premium,source,transapp_code",
            "list" => "premium,policy_number,renewal_expiry_date,is_renewal,email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info,next_followup_date,lost_reason,source,transapp_code",
            "update" => "previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code",
            "show" => "id,next_followup_date,lost_reason,is_renewal",
        ];
    }
    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            "create" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,premium,source,transapp_code,renewal_expiry_date",
            "list" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,others_info,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,premium,lead_type_id,renewal_expiry_date,previous_quote_id",
            "update" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "show" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date,previous_quote_id",
        ];
    }

    public function getDuplicateEntityByCode($code)
    {
        return LifeQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function createDuplicate($parentRecord)
    {
        $quote = new LifeQuote();
        $quote->parent_duplicate_quote_id = $parentRecord->code;
        $response = CapiRequestService::getUUID(QuoteTypeId::Life);
        if($response) {
            $quote->uuid = $response->uuid;
            $quote->code = 'LIF-'. $response->uuid;
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
        
        $audits = DB::table('audits as a')
        ->select(
            'a.created_at as ModifiedAt',
            DB::raw('(SELECT name from users where id = a.user_id) as ModifiedBy'),
            DB::raw("(SELECT TEXT FROM quote_status WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.quote_status_id'))) AS NewStatus"),
            DB::raw("(SELECT NAME FROM users WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.advisor_id'))) AS NewAdvisor"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.notes')) AS NewNotes")
        )
        ->where(function ($query) {
            $query->where('a.auditable_type', 'App\Models\LifeQuote')
            ->orWhere('a.auditable_type', 'App\Models\LifeQuoteRequestDetail');
        })
        ->where(function ($query) {
            $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.quote_status_id')"))
            ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.notes')"))
            ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.advisor_id')"));
        })
        ->where(function ($query) use ($id) {
            $detailObjId = LifeQuoteRequestDetail::where('life_quote_request_id', $id)->first();
            if($detailObjId) {
                $query->where('a.auditable_id', $id)
                ->orWhere('a.auditable_id', $detailObjId->id);
            } else {
                $query->where('a.auditable_id', $id);
            }
        })
        ->orderBy('a.created_at', 'DESC')->get();
        return $audits;
    }

}
