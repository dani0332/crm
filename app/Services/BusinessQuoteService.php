<?php

namespace App\Services;

use App\Enums\DatabaseColumnsString;
use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GetUserTree;
use App\Traits\RolePermissionConditions;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessQuoteService extends BaseService
{
    protected $query;

    use GetUserTree;
    use RolePermissionConditions;
    use AddPremiumAllLobs;

    protected $leadAllocationService;

    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
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
                'bqr.policy_number',
                'bqr.previous_quote_id',
                'bqr.renewal_expiry_date',
                'bqr.renewal_batch',
                'bqr.previous_quote_policy_number',
                'bqr.previous_policy_expiry_date',
                'bqr.previous_quote_policy_premium',
                'bqr.gender',
                'bqr.device',
                'bqr.customer_id',
                'bqr.parent_duplicate_quote_id',
                'bqr.renewal_import_code'
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
        if (! empty($CDBID)) {
            $query->where('bqr.id', '=', $CDBID);
        }
        if (! empty($email)) {
            $query->where('bqr.email', '=', $email);
        }
        if (! empty($mobile_no)) {
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
            ->where('bqrd.next_followup_date', '<', date('Y-m-d H:i:s'))
            ->whereIn('qs.id', [QuoteStatusEnum::FollowedUp, QuoteStatusEnum::QualificationPending, QuoteStatusEnum::Quoted, QuoteStatusEnum::FTCPending, QuoteStatusEnum::FTCSent, QuoteStatusEnum::MissingDocumentsRequested, QuoteStatusEnum::PolicyDocumentsPending, QuoteStatusEnum::PaymentPending, QuoteStatusEnum::PendingWithUW, QuoteStatusEnum::ApplicationPending, QuoteStatusEnum::InNegotiation]);

        return $query;
    }

    public function getBusinessLeadsForAdvisor($request)
    {
        $query = DB::table('business_quote_request as bqr')
            ->select(
                'bqr.id',
                'bqr.uuid',
                'bqr.code',
                'qs.text as leadStatus',
                'bqr.created_at as createdAt',
                'bqr.quote_status_id',
                'bqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'bqr.updated_at as updatedAt',
                'bqr.source as leadSource',
                'bqr.company_name as companyName',
                'bqr.premium',
                'bqr.email',
                'bqr.mobile_no',
                'bqrd.next_followup_date as nextFollowupDate',
                'bqr.previous_quote_id',
                'ps.text as paymentStatus',
                'bqr.renewal_batch as renewalBatch',
                'bqr.previous_quote_policy_number as previousPolicyNumber',
                DB::raw('DATE_FORMAT(bqr.previous_policy_expiry_date, "%d-%m-%Y") as previousPolicyExpiryDate'),
                'bqr.previous_quote_policy_premium as previousPolicyPremium',
                'bqr.first_name as firstName',
                'bqr.last_name as lastName',
            )
            ->leftJoin('business_quote_request_detail as bqrd', 'bqrd.business_quote_request_id', '=', 'bqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'bqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'bqrd.advisor_assigned_by_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'bqr.payment_status_id')
            ->where('bqr.advisor_id', Auth::user()->id)
            ->where('qs.id', '!=', 9);
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('bqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->nfdSart)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->nfdEnd)->endOfDay()->toDateTimeString();
            $query->whereBetween('bqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 3) {
                $column = 'bqr.created_at';
            }
            if ($column == 4) {
                $column = 'bqrd.advisor_assigned_date';
            }
            if ($column == 7) {
                $column = 'bqrd.next_followup_date';
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
        if (isset($request->premium) && $request->premium != '') {
            $query->where('bqr.premium', $request->premium);
        }
        if (isset($request->clientName) && $request->clientName != '') {
            $query->where('clientName', $request->clientName);
        }
        if (isset($request->policy_number) && $request->policy_number != '') {
            $query->where('bqr.policy_number', $request->policy_number);
        }
        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $query->where('bqr.mobile_no', $request->mobile_no);
        }
        if (isset($request->paymentStatus)) {
            $query->where('bqr.payment_status_id', $request->paymentStatus);
        }
        if (Auth::user()->isRenewalAdvisor()) {
            $query->whereNotNull('bqr.previous_quote_policy_number');
        }
        if (Auth::user()->isNewBusinessAdvisor()) {
            $query->whereNull('bqr.previous_quote_policy_number');
        }
        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $query->where('bqr.renewal_batch', $request->renewal_batch);
        }
        if (isset($request->previous_policy_number) && $request->previous_policy_number != '') {
            $query->where('bqr.previous_quote_policy_number', $request->previous_policy_number);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $query->where('bqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '' && $request->previous_policy_expiry_date_end != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $query->whereBetween('bqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
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

        if (empty($childRecord)) {
            $childRecord = $this->createDetailEntity($id);
        }
        $childRecord->advisor_assigned_by_id = Auth::user()->id;
        $childRecord->advisor_assigned_date = Carbon::now();
        $childRecord->save();
    }

    public function getDetailEntity($id)
    {
        $entity = BusinessQuoteRequestDetail::where('business_quote_request_id', $id)->first();
        if (! $entity) {
            $entity = $this->createDetailEntity($id);
        }

        return  $entity;
    }

    public function createDetailEntity($id)
    {
        return BusinessQuoteRequestDetail::create([
            'business_quote_request_id' => $id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function getSelectedLostReason($id)
    {
        $entity = BusinessQuoteRequestDetail::where('business_quote_request_id', $id)->first();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
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
        $dataArr = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'numberOfEmployees' => $request->number_of_employees,
            'mobileNo' => $request->mobile_no,
            'companyName' => $request->company_name,
            'briefDetails' => $request->brief_details,
            'premium' => $request->premium,
            'businessTypeOfInsuranceId' => $request->business_type_of_insurance_id,
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
        ];
        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-business-quote', $dataArr);

        if (isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::BusinessQuote, $request, $response);
        }

        return $response;
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
        } elseif ($isNewManager || $isNewAdvisor) {
            $searchProperties = $model->newBusinessSearchProperties;
        } else {
            $searchProperties = $model->searchProperties;
        }
        if ($request->ajax()) {
            if (! isset($request->email) && $request->email == '') {
                $this->query->where('bqr.quote_status_id', '!=', 9);
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
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('bqr.created_at', [$dateFrom, $dateTo]);
            }
            if (Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::CORPLINE) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::Business) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::GM)) {
                // if user has advisor Role then fetch leads assigned to the user only
                $this->query->where('bqr.advisor_id', Auth::user()->id);    // fetch leads assigned to the user
            }
            if (isset($request->code) && $request->code != '') {
                $this->query->where('bqr.code', $request->code);
            }
            if (isset($request->first_name) && $request->first_name != '') {
                $this->query->where('bqr.first_name', $request->first_name);
            }
            if (isset($request->last_name) && $request->last_name != '') {
                $this->query->where('bqr.last_name', $request->last_name);
            }
            if (isset($request->email) && $request->email != '') {
                $this->query->where('bqr.email', $request->email);
            }
            if (isset($request->mobile_no) && $request->mobile_no != '') {
                $this->query->where('bqr.mobile_no', $request->mobile_no);
            }
            if (isset($request->policy_number) && $request->policy_number != '') {
                $this->query->where('bqr.policy_number', $request->policy_number);
            }
            if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
                $this->query->where('bqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            }
            if (isset($request->renewal_batch) && $request->renewal_batch != '') {
                $this->query->where('bqr.renewal_batch', $request->renewal_batch);
            }
            if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('bqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
                $this->query->where('bqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
            }

            $this->whereBasedOnRole($this->query, 'bqr');
            if (isset($request->is_renewal) && $request->is_renewal != '') {
                if ($request->is_renewal == quoteTypeCode::yesText) {
                    $this->query->whereNotNull('bqr.previous_quote_policy_number');
                }
                if ($request->is_renewal == quoteTypeCode::noText) {
                    $this->query->whereNull('bqr.previous_quote_policy_number');
                }
            }
            foreach ($searchProperties as $item) {
                if (! empty($request[$item]) && $item != 'created_at') {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } elseif ($item == 'advisor_id' && is_array($request[$item]) && ! empty($request[$item])) {
                        if ($request[$item][0] == 'null') {
                            $this->query->whereNull('advisor_id');
                        } else {
                            $this->query->whereIn('advisor_id', $request[$item]);
                        }
                    } elseif ($item == DatabaseColumnsString::QUOTE_STATUS_ID && is_array($request[$item]) && ! empty($request[$item])) {
                        $this->query->whereIn('quote_status_id', $request[$item]);
                    } else {
                        $skipped = ['is_renewal', 'previous_policy_expiry_date'];
                        if (in_array($item, $skipped)) {
                            continue;
                        }
                        $this->query->where($this->getQuerySuffix($item).'.'.$item, $request[$item]);
                    }
                }
            }
        }

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
            $isAdmin = Auth::user()->hasRole('ADMIN');
            if ($isAdmin || $isManagerORDeputy == '1') {
                if ($column == 10) {
                    $column = 'bqr.created_at';
                }
                if ($column == 11) {
                    $column = 'bqr.updated_at';
                }
                if ($column == 5) {
                    $column = 'bqrd.next_followup_date';
                }
            } else {
                if ($column == 9) {
                    $column = 'bqr.created_at';
                }
                if ($column == 10) {
                    $column = 'bqr.updated_at';
                }
                if ($column == 4) {
                    $column = 'bqrd.next_followup_date';
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
            $businessQuote->gender = $request->gender;
            $businessQuote->brief_details = $request->brief_details;
            $businessQuote->premium = $request->premium;
            $businessQuote->business_type_of_insurance_id = $request->business_type_of_insurance_id;
            $businessQuote->number_of_employees = $request->number_of_employees;
            if (isset($request->group_medical_type_id)) {
                $businessQuote->group_medical_type_id = $request->group_medical_type_id;
            }
            $businessQuote->save();

            if (isset($request->return_to_view)) {
                return redirect('quote/business/'.$businessQuote->id)->with('success', 'Business Quote has been updated');
            }
        } else {
            return redirect('quote/business')->with('message', 'Business Quote not found');
        }
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'code' => 'input|title',
            'first_name' => 'input|text|required',
            'last_name' => 'input|text|required',
            'email' => 'input|email|required',
            'mobile_no' => 'input|title|number|required',
            'company_name' => 'input|text|required',
            'next_followup_date' => 'input|date|title|range',
            'transapp_code' => 'readonly|none',
            'source' => 'input|text',
            'policy_number' => 'input|text',
            'lost_reason' => 'input|text',
            'advisor_id' => 'select|title|multiple',
            'quote_status_id' => 'select|title|multiple',
            'created_at' => 'input|date|title|range',
            'updated_at' => 'input|date|title',
            'premium' => 'input|number|title',
            'number_of_employees' => 'input|number|title',
            'business_type_of_insurance_id' => 'select|title|required',
            'brief_details' => 'textarea|required',
            'previous_quote_id' => 'readonly|title',
            'is_renewal' => 'static|'.GenericRequestEnum::Yes.','.GenericRequestEnum::No.'',
            'renewal_expiry_date' => 'input|date|title|range',
            'renewal_batch' => 'input|none',
            'previous_policy_expiry_date' => 'input|date|title|range',
            'previous_quote_policy_number' => 'input|title',
            'previous_quote_policy_premium' => 'input|title',
            'gender' => '|static|'.GenericRequestEnum::MALE_SINGLE.','.GenericRequestEnum::FEMALE_SINGLE.','.GenericRequestEnum::FEMALE_MARRIED.'',
            'parent_duplicate_quote_id' => 'input|title',
            'renewal_import_code' => 'input|text',
            'device' => 'input|title',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'business_type_of_insurance_id':
                $title = 'Business Insurance Type';
                break;
            case 'ilivein_accommodation_type_id':
                $title = 'I Live In';
                break;
            case 'number_of_employees':
                $title = 'Number of Employees';
                break;
            case 'mobile_no':
                $title = 'Mobile Number';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'next_followup_date':
                $title = 'Next Followup Date';
                break;
            case 'code':
                $title = 'CDB ID';
                break;
            case 'advisor_id':
                $title = 'Assigned To';
                break;
            case 'quote_status_id':
                $title = 'Lead Status';
                break;
            case 'previous_quote_id':
                $title = 'Previous Quote Id';
                break;
            case 'renewal_expiry_date':
                $title = 'Expiry Date';
                break;
            case 'previous_quote_policy_number':
                $title = 'Previous Policy Number';
                break;
            case 'previous_policy_expiry_date':
                $title = 'Previous Policy Expiry Date';
                break;
            case 'previous_quote_policy_premium':
                $title = 'Previous Policy Premium';
                break;
            case 'premium':
                $title = 'Premium';
                break;
            case 'parent_duplicate_quote_id':
                $title = 'Parent CDB ID';
                break;
            case 'device':
                $title = 'Device';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date,renewal_import_code,device',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,email,mobile_no,brief_details,dob,renewal_expiry_date,next_followup_date,renewal_import_code,device',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date,renewal_import_code,device',
            'show' => 'is_renewal,previous_quote_id,quote_status_id',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'company_name', 'business_type_of_insurance_id'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'renewal_batch', 'previous_quote_policy_number', 'previous_policy_expiry_date', 'previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            'create' => 'parent_duplicate_quote_id,gender,previous_quote_policy_premium,renewal_expiry_date,previous_policy_expiry_date,policy_number,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,premium,source,transapp_code,renewal_import_code,device',
            'list' => 'parent_duplicate_quote_id,gender,renewal_expiry_date,policy_number,is_renewal,previous_quote_id,email,mobile_no,brief_details,dob,next_followup_date,lost_reason,source,transapp_code,business_type_of_insurance_id,number_of_employees,renewal_import_code,device',
            'update' => 'parent_duplicate_quote_id,gender,previous_quote_policy_premium,renewal_expiry_date,previous_policy_expiry_date,policy_number,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,source,transapp_code,renewal_import_code,device',
            'show' => 'gender,renewal_expiry_date,is_renewal,policy_number,previous_quote_id',
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date,renewal_import_code,device',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,previous_policy_expiry_date,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,premium,lead_type_id,renewal_expiry_date,previous_quote_id,renewal_import_code,device',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date,renewal_import_code,device',
            'show' => 'member_category_id,salary_band_id,is_renewal,id,previous_quote_id',
        ];
    }

    public function getDuplicateEntityByCode($code)
    {
        return BusinessQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function getEntityPlainByUUID($uuid)
    {
        return BusinessQuote::where('uuid', $uuid)->first();
    }

    public function processManualLeadAssignment($request): array
    {
        if ($request->selectTmLeadId == '' || $request->selectTmLeadId == null) {
            $leadsIds = array_map('intval', explode(',', trim($request->entityId, ',')));
        } else {
            $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        }
        $userId = (int) $request->assigned_to_id_new;
        Log::info('Leads ids to assign: '.json_encode($leadsIds));
        $result = [];
        foreach ($leadsIds as $leadId) {
            $lead = $this->getEntityPlain($leadId);
            $lead->advisor_id = $userId;
            $lead->save();
            $this->updateChildRecord($lead->id);
        }

        return $result;
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
}
