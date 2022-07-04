<?php

namespace App\Services;

use App\Enums\LeadSourceTypes;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\QuoteStatus;
use Illuminate\Http\Request;
use DB;
use Auth;
use \Carbon\Carbon;
use Hidehalo\Nanoid\Client;
use Config;
use App\Traits\RolePermissionConditions;
use App\Traits\CustomerAdditionalInfo as CustomerAdditionalInfoTrait;
use App\Enums\quoteTypeCode;
use App\Enums\DatabaseColumnsString;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GetUserTree;
use Illuminate\Support\Facades\Log;

class HealthQuoteService extends BaseService
{
    protected $query;
    protected $leadAllocationService;
    use GetUserTree;
    use RolePermissionConditions;
    use CustomerAdditionalInfoTrait;
    use AddPremiumAllLobs;
    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
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
            'hqr.gender',
            'hqr.has_dental',
            'hqr.health_team_type',
            'hqr.has_home',
            'hqr.premium',
            'hqr.policy_number',
            'hqr.is_ebp_renewal',
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
            'hqrd.transapp_code',
            'hqrd.notes',
            'hqr.lead_type_id',
            'lt.TEXT AS lead_type_id_text',
            'ls.text as lost_reason',
            'hqr.previous_quote_id',
            'hqr.salary_band_id',
            'sb.text as salary_band_id_text',
            'hqr.member_category_id',
            'mc.text as member_category_id_text',
            'hqr.renewal_expiry_date',
            'hqr.renewal_batch',
            'hqr.previous_quote_policy_number',
            'hqr.previous_policy_expiry_date',
            'hqr.previous_quote_policy_premium',
            'hqr.device',
            'hqr.wcu_id',
        )
            ->leftJoin('marital_status as ms', 'ms.id', '=', 'hqr.marital_status_id')
            ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'hqrd.lost_reason_id')
            ->leftJoin('health_cover_for as hcf', 'hcf.id', '=', 'hqr.cover_for_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'hqr.nationality_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'hqr.emirate_of_your_visa_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('health_lead_type as lt', 'lt.id', '=', 'hqr.lead_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
            ->leftJoin('salary_band as sb', 'sb.id', '=', 'hqr.salary_band_id')
            ->leftJoin('member_category as mc', 'mc.id', '=', 'hqr.member_category_id');
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
            $entity = $this->createDetailEntity($id);
        }
        return $entity;
    }

    public function createDetailEntity($id)
    {
        return HealthQuoteRequestDetail::create([
            'health_quote_request_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getLeadsForAssignment()
    {
        return HealthQuote::orderBy('created_at', 'desc')->get();
    }
    public function saveHealthQuote(Request $request)
    {
        $sourceName = $request->is_ebp_renewal == 'on' ? LeadSourceTypes::EBPRENEWALS : Config::get('constants.SOURCE_NAME');
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
            "leadTypeId" => $request->lead_type_id,
            "referenceUrl" => $appUrl,
            "dob" => $request->dob,
            "gender" => $request->gender,
            "is_ebp_renewal" => $request->is_ebp_renewal == 'on' ? true : false,
            "coverForId" => $request->cover_for_id,
            "nationalityId" => $request->nationality_id,
            "hasDental" => $request->has_dental == 'on' ? true : false,
            "hasWorldwideCover" => $request->has_worldwide_cover == 'on' ?  true : false,
            "hasHome" => $request->has_home == 'on' ? true : false,
            "emirateOfYourVisaId" => $request->emirate_of_your_visa_id,
            "salaryBandId" => $request->salary_band_id,
            "memberCategoryId" => $request->member_category_id
        );
        if (!Auth::user()->hasRole("ADMIN")) $dataArr['advisorId'] = Auth::user()->id;
         $response = CapiRequestService::sendCAPIRequest('/api/v1-save-health-quote', $dataArr);
        if(isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::HealthQuote, $request, $response);
            return $this->createUpdateCustomerInfo($request, $request->email, $response->quoteUID, quoteTypeCode::HealthQuote);
        }else {
            return $response;
        }
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
        } else if ($isNewManager || $isNewAdvisor) {
            $searchProperties = $model->newBusinessSearchProperties;
        } else {
            $searchProperties = $model->searchProperties;
        }
        if ($request->ajax()) {
            if (!isset($request->email) && $request->email == '') {
                $this->query->where('hqr.quote_status_id', '!=', 9);
            }
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
            if (Auth::user()->isSpecificTeamAdvisor('Health') || Auth::user()->isSpecificTeamAdvisor('EBP') || Auth::user()->isSpecificTeamAdvisor('RM')) {
                // if user has advisor Role then fetch leads assigned to the user only
                $this->query->where('hqr.advisor_id', Auth::user()->id);    // fetch leads assigned to the user
            }
            if (isset($request->code) && $request->code != '') {
                $this->query->where('hqr.code', $request->code);
            }
            if (isset($request->first_name) && $request->first_name != '') {
                $this->query->where('hqr.first_name', $request->first_name);
            }
            if (isset($request->last_name) && $request->last_name != '') {
                $this->query->where('hqr.last_name', $request->last_name);
            }
            if (isset($request->email) && $request->email != '') {
                $this->query->where('hqr.email', $request->email);
            }
            if (isset($request->mobile_no) && $request->mobile_no != '') {
                $this->query->where('hqr.mobile_no', $request->mobile_no);
            }
            if (isset($request->policy_number) && $request->policy_number != '') {
                $this->query->where('hqr.policy_number', $request->policy_number);
            }
            if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
                $this->query->where('hqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            }
            if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->renewal_batch) && $request->renewal_batch != '') {
                $this->query->where('hqr.renewal_batch', $request->renewal_batch);
            }
            if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
                $this->query->where('hqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
            }
            $this->whereBasedOnRole($this->query,'hqr');

            if (isset($request->is_renewal) && $request->is_renewal != '') {
                if ($request->is_renewal ==  quoteTypeCode::yesText)
                    $this->query->whereNotNull('hqr.previous_quote_id');
                if ($request->is_renewal ==  quoteTypeCode::noText)
                    $this->query->whereNull('hqr.previous_quote_id');
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else if ($item == 'advisor_id' && is_array($request[$item]) && !empty($request[$item])) {
                        if ($request[$item][0] == 'null')
                            $this->query->whereNull('advisor_id');
                        else
                            $this->query->whereIn('advisor_id', $request[$item])->orWhereIn('wcu_id', $request[$item]);
                    }
                    else if ($item == DatabaseColumnsString::QUOTE_STATUS_ID && is_array($request[$item]) && !empty($request[$item])) {
                        $this->query->whereIn('quote_status_id', $request[$item]);
                    } else {
                        $skipped = array('is_renewal','previous_policy_expiry_date','next_followup_date');
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
                    $column = "hqr.created_at";
                }
                if ($column == 7) {
                    $column = "hqr.updated_at";
                }
                if ($column == 9) {
                    $column = "hqrd.next_followup_date";
                }
            } else {
                if ($column == 5) {
                    $column = "hqr.created_at";
                }
                if ($column == 6) {
                    $column = "hqr.updated_at";
                }
                if ($column == 8) {
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
        $sourceName = $request->is_ebp_renewal == 'on' ? LeadSourceTypes::EBPRENEWALS : Config::get('constants.SOURCE_NAME');
        $healthQuote = HealthQuote::where('uuid', $id)->first();
        $healthQuote->first_name = $request->first_name;
        $healthQuote->last_name = $request->last_name;
        $healthQuote->details = $request->details;
        $healthQuote->preference = $request->preference;
        $healthQuote->source = $sourceName;
        $healthQuote->marital_status_id = $request->marital_status_id;
        $healthQuote->dob = $request->dob;
        $healthQuote->gender = $request->gender;
        $healthQuote->cover_for_id = $request->cover_for_id;
        $healthQuote->nationality_id = $request->nationality_id;
        $healthQuote->is_ebp_renewal = $request->is_ebp_renewal == 'on' ? true : false;
        $healthQuote->has_dental = $request->has_dental == 'on' ? true : false;
        $healthQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? true : false;
        $healthQuote->has_home = $request->has_home == 'on' ? true : false;
        $healthQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $healthQuote->premium = $request->premium;
        if ($healthQuote->salary_band_id != $request->salary_band_id || $healthQuote->member_category_id != $request->member_category_id) {
            $healthQuote->quote_updated_at = now();
        }
        $healthQuote->salary_band_id = $request->salary_band_id;
        $healthQuote->member_category_id = $request->member_category_id;
        $healthQuote->save();
        $this->createUpdateCustomerInfo($request, $healthQuote->email, $healthQuote->uuid, quoteTypeCode::HealthQuote);
        if (isset($request->return_to_view))
            return redirect("quote/health/" . $id)->with('success', 'Health Quote has been updated');
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
                $query->where('a.auditable_type', 'App\Models\HealthQuote')
                    ->orWhere('a.auditable_type', 'App\Models\HealthQuoteRequestDetail');
            })
            ->where(function ($query) {
                $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.quote_status_id')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.notes')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.advisor_id')"));
            })
            ->where(function ($query) use ($id) {
                $detailObjId = HealthQuoteRequestDetail::where('health_quote_request_id', $id)->first();
                if ($detailObjId) {
                    $query->where('a.auditable_id', $id)
                        ->orWhere('a.auditable_id', $detailObjId->id);
                } else {
                    $query->where('a.auditable_id', $id);
                }
            })
            ->orderBy('a.created_at', 'DESC')->get();
        return $audits;
    }

    public function getHealthOverDueFollowups()
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
                'hqr.premium',
                'hqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqrd.advisor_assigned_by_id')
            ->whereIn('qs.text', ['Followed Up', 'Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'])
            ->where('hqrd.next_followup_date', '<', date('Y-m-d H:i:s'))
            ->where('hqr.advisor_id', Auth::user()->id);
        return $query;
    }

    public function getHealthLeadsForAdvisor($request)
    {
        $query = DB::table('health_quote_request as hqr')
            ->select(
                'hqr.id',
                'hqr.uuid',
                'hqr.code',
                'qs.text as leadStatus',
                'hqr.created_at as createdAt',
                'hqr.quote_status_id',
                'hqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'hqr.updated_at as updatedAt',
                'hqr.policy_number as policy_number',
                'hqr.email as email',
                'hqr.mobile_no as mobile_no',
                'hqr.source as leadSource',
                'hqr.premium',
                'hqrd.next_followup_date as nextFollowupDate',
                'hqr.previous_quote_id',
                'ps.text as paymentStatus',
                'hqr.renewal_batch as renewalBatch',
                'hqr.previous_quote_policy_number as previousPolicyNumber',
                DB::raw('DATE_FORMAT(hqr.previous_policy_expiry_date, "%d-%m-%Y") as previousPolicyExpiryDate'),
                'hqr.previous_quote_policy_premium as previousPolicyPremium',
                'hqr.first_name as firstName',
                'hqr.last_name as lastName',
            )
            ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqrd.advisor_assigned_by_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'hqr.payment_status_id')
            ->where('hqr.quote_status_id', '!=', 9)
            ->orderBy('hqr.created_at', "DESC");

            if(auth()->user()->isHealthWCUAdvisor())
            {
                $query->where('hqr.wcu_id', auth()->id());
            }else{
                $query->where('hqr.advisor_id', auth()->id());
            }

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
        if (Auth::user()->isRenewalAdvisor()) {
            $query->whereNotNull('hqr.previous_quote_id');
        }
        if (Auth::user()->isNewBusinessAdvisor()) {
            $query->whereNull('hqr.previous_quote_id');
        }
        if (isset($request->paymentStatus) && $request->paymentStatus != '') {
            $query->where('hqr.payment_status_id', $request->paymentStatus);
        }
        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $query->where('hqr.renewal_batch', $request->renewal_batch);
        }
        if (isset($request->previous_policy_number) && $request->previous_policy_number != '') {
            $query->where('hqr.previous_quote_policy_number', $request->previous_policy_number);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $query->where('hqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '' && $request->previous_policy_expiry_date_end != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $query->whereBetween('hqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
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
        if (empty($childRecord)) {
            $childRecord = $this->createDetailEntity($id);
        }
        if($childRecord->advisor_id != null){
            $childRecord->advisor_assigned_by_id = Auth::user()->id;
            $childRecord->advisor_assigned_date = now();
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
            "health_team_type" => "|static|default:All|All,RM-NB,RM-Speed,EBP,Wow-Call,No-Type",
            "next_followup_date" => "input|date|title|range",
            "transapp_code" => "readonly|none",
            "lost_reason" => "input|text",
            "premium" => "input|number",
            "policy_number" => "input|text",
            "preference" => "input|text",
            "details" => "input|text",
            "is_ebp_renewal"  => "input|checkbox|title",
            "source" => "input|text|title",
            "marital_status_id" => "select|title|required",
            "cover_for_id" => "select|title|required",
            "nationality_id" => "select|title|required",
            "lead_type_id" => "select|title|required",
            "has_dental" => "input|checkbox|title",
            "has_worldwide_cover" => "input|checkbox|title",
            "has_home" => "input|checkbox|title",
            "emirate_of_your_visa_id" => "select|title|required",
            "previous_quote_id" => "readonly|title",
            "renewal_expiry_date" => "input|date|title|range",
            "is_renewal" => "|static|Yes,No",
            "salary_band_id" => "select|title|required",
            "member_category_id" => "select|title|required",
            "gender" => "|static|Male,Female",
            "renewal_batch" => "input|none",
            "previous_quote_policy_number" => "input|title",
            "previous_policy_expiry_date" => "input|date|title|range",
            "previous_quote_policy_premium" => "input|title",
            "device" => "input|title",
        );
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "list" => "device,previous_policy_expiry_date,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,is_renewal,gender,previous_quote_id,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,renewal_expiry_date",
            "update" => "device,previous_policy_expiry_date,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "show" => "is_renewal,id",
        ];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'renewal_batch', 'previous_quote_policy_number', 'previous_policy_expiry_date', 'previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            "create" => "premium,device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "list" => "premium,device,policy_number,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,renewal_expiry_date",
            "update" => "premium,device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "show" => "premium,member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date",
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            "create" => "device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "list" => "device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,renewal_expiry_date,previous_quote_id",
            "update" => "device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "show" => "previous_quote_policy_premium,renewal_expiry_date,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date,previous_quote_id",
        ];
    }
    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'health_team_type', 'next_followup_date', 'is_renewal'];
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
            case 'is_ebp_renewal':
                $title = 'Is EBP Renewal';
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
            case 'lead_type_id':
                $title = "Lead Type";
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
            case 'previous_quote_id':
                $title = "Previous Quote Id";
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
            case 'salary_band_id':
                $title = "Salary Band";
                break;
            case 'member_category_id':
                $title = "Member Category";
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

    public function getDuplicateEntityByCode($code)
    {
        return HealthQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function createDuplicate($parentRecord)
    {
        $quote = new HealthQuote();
        $quote->parent_duplicate_quote_id = $parentRecord->code;
        $response = CapiRequestService::getUUID(QuoteTypeId::Health);
        if ($response) {
            $quote->uuid = $response->uuid;
            $quote->code = 'HEA-' . $response->uuid;
        }
        $quote->quote_status_id = QuoteStatusEnum::NewLead;
        $quote->first_name = $parentRecord->first_name;
        $quote->last_name = $parentRecord->last_name;
        $quote->email = $parentRecord->email;
        $quote->advisor_id = Auth::user()->id;
        $quote->mobile_no = $parentRecord->mobile_no;
        $quote->save();
    }

    public function getQuotePlans($id)
    {
        $quoteUuId = HealthQuote::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = Config::get('constants.KEN_API_ENDPOINT') . '/get-health-quote-plans';
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
            } else if (isset($response->msg)) {
                $responseBodyAsString = $response->msg;
            } else {
                $responseBodyAsString = "Quote unavailable for the selected current location and region. Please call 800 ALFRED.";
            }

            return $responseBodyAsString;
        }
    }

    public function validateRequest($request)
    {
        $userId = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId == null || $request->selectTmLeadId == '' ? $request->entityId : $request->selectTmLeadId;
        if ($leadsIds == '' || $leadsIds == null) {
            return 'Please select lead(s) to assign';
        }
        if (substr($leadsIds, 0, 1) == ',') {
            $leadsIds = substr($leadsIds, 1);
        }
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        foreach ($leadsIds as $leadId) {
            $entity = $this->getEntityPlain($leadId);
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                return 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
            }
        }
        if ($userId == '' || $userId == null) {
            return 'Please select user to assign leads';
        }
        return 'true';
    }

    public function removePreviousAdvisorAndUpdateStatus($entity, $quoteStatusId)
    {
        $startOfDayToday = now()->startOfDay();
        $entityDetail = $this->getDetailEntity($entity->id);
        $lastAssignedAdvisorDate = Carbon::parse($entityDetail->advisor_assigned_date)->startOfDay();
        if ($lastAssignedAdvisorDate == $startOfDayToday) {
            $this->leadAllocationService->removeLeadAllocationForOldAdvisor($entity);
        }
        $entity->advisor_id = null;
        $entity->quote_status_id = $quoteStatusId;
        $entity->save();

    }

    public function assignWCU($request): array
    {
        $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        Log::info('Leads ids to assign: ' . json_encode($leadsIds));
        $userId = $request->assigned_to_id_new;
        $result = [];
        foreach ($leadsIds as $leadId) {
            $lead = $this->getEntityPlain($leadId);
            if($this->isLeadTransactionApproved($lead)) {
                Log::info('Cannot assign WCU as lead is in Transaction Approved state , lead id: ' . $leadId);
                array_push($result, ['leadId' => $lead->code, 'msg' => 'Cannot assign WCU as lead is in Transaction Approved state']);
                continue;
            }
            $lead->advisor_id = null;
            $lead->quote_status_id = QuoteStatusEnum::NewLead;
            $lead->wcu_id = $userId;
            $lead->health_team_type = $request->assign_team;
            $lead->save();
            Log::info('WCU advisor : ' . $userId . ' assigned to lead: ' . $leadId);
        }
        return $result;
    }

    public function assignHealthTeam($request, $lead): bool
    {
        if($this->isLeadTransactionApproved($lead)) {
            Log::info('Cannot assign Health Team as lead is in Transaction Approved state');
            return false;
        }
        if($lead->health_team_type != null && $lead->advisor_id != null) {
            Log::info('Removing previous advisor as lead already assigned to a health team');
            $this->removePreviousAdvisorAndUpdateStatus($lead, QuoteStatusEnum::Qualified);
        }
        $selectedTeam = $request->get('assign_team');
        if ($selectedTeam == quoteTypeCode::GM) {
            Log::info('Assigning lead to GM');
            $this->convertLeadToGM($lead);
            $lead->health_team_type = quoteTypeCode::GM;
            $lead->save();

        } else {
            Log::info('Assigning lead to '. $selectedTeam. ' team');
            $lead->health_team_type = $selectedTeam;
            if($lead->quote_status_id == QuoteStatusEnum::Qualified) {
                $lead->wcu_id = null;
            }
            $lead->save();
        }
        return true;
    }

    public function isLeadTransactionApproved($lead): bool
    {
        if ($lead->quote_status_id == QuoteStatusEnum::TransactionApproved) {
            return true;
        }
        return false;
    }

    public function processManualLeadAssignment($request): array
    {
        if($request->selectTmLeadId == '' || $request->selectTmLeadId == null) {
            $leadsIds = array_map('intval', explode(',', trim($request->entityId, ',')));
        }
        else{
            $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        }
        $userId = (int)$request->assigned_to_id_new;
        Log::info('Leads ids to assign: ' . json_encode($leadsIds));
        $result = [];
        foreach($leadsIds as $leadId)
        {
            $lead = $this->getEntityPlain($leadId);
            if(strtolower($request->modelType) == strtolower(quoteTypeCode::Health))
            {
                if($lead->health_team_type == null || $lead->health_team_type == '') {
                    Log::info('Lead with id: ' . $leadId . ' is not assigned to any health team');
                    $msg = 'Health team is missing please select health team first';
                    array_push($result, [ 'leadId' => $lead->code, 'msg' => $msg ]);
                    continue;
                }
                if($this->leadAllocationService->checkIfAdvisorCanTakeLead($userId)){
                    Log::info('Advisor : ' . $userId . ' can take lead: ' . $leadId);
                    $this->leadAllocationService->assignLead($lead, $userId, true);
                    $this->updateChildRecord($lead->id);
                    Log::info('Lead: ' . $leadId . ' assigned to advisor: ' . $userId);
                }else{
                    Log::info('Advisor : ' . $userId . ' cannot take lead: ' . $leadId);
                    $msg = 'Advisor is not allowed to take lead with CDBID : ' . $lead->code;
                    array_push($result, [ 'leadId' => $lead->code, 'msg' => $msg ]);
                    continue;
                }
            }
            else{
                $lead->advisor_id = $userId;
                $lead->save();
            }
        }
        return $result;
    }
    public function getEntityPlainByUUID($uuid)
    {
        return HealthQuote::where('uuid', $uuid)->first();
    }

}
