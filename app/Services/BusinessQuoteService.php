<?php

namespace App\Services;

use App\Enums\CustomerTypeEnum;
use App\Enums\DatabaseColumnsString;
use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\QuoteBatches;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\GetUserTreeTrait;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BusinessQuoteService extends BaseService
{
    protected $query;
    use AddPremiumAllLobs, GenericQueriesAllLobs, GetUserTreeTrait, RolePermissionConditions;

    protected $leadAllocationService;

    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
        $this->query = DB::table('business_quote_request as bqr')
            ->select(
                'bqr.id',
                'bqr.uuid',
                'bqr.code',
                DB::raw('DATE_FORMAT(bqr.created_at, "%d-%b-%Y %r") as created_at'),
                DB::raw('DATE_FORMAT(bqr.updated_at, "%d-%b-%Y %r") as updated_at'),
                'bqr.first_name',
                'bqr.last_name',
                'bqr.email',
                'bqr.mobile_no',
                'bqr.company_name AS business_company_name',
                'bqr.company_address AS business_company_address',
                'bqr.brief_details',
                'bqr.number_of_employees',
                'bqr.business_type_of_insurance_id',
                'bti.TEXT AS business_type_of_insurance_id_text',
                'bqr.advisor_id',
                'u.name as advisor_id_text',
                'bqr.previous_advisor_id',
                'uadv.name AS previous_advisor_id_text',
                'bqr.quote_status_id',
                'qs.text as quote_status_id_text',
                'bqr.premium',
                'bqrd.next_followup_date',
                'bqrd.notes',
                'bqrd.transapp_code',
                'bqrd.insly_id',
                'ls.text as lost_reason',
                'lu.text as transaction_type_text',
                'bqr.source',
                'bqr.policy_number',
                'bqr.previous_quote_id',
                'bqr.policy_expiry_date',
                'bqr.renewal_batch',
                'rb.name as renewal_batch_text',
                'bqr.previous_quote_policy_number',
                'bqr.previous_policy_expiry_date',
                'bqr.previous_quote_policy_premium',
                'bqr.gender',
                'bqr.device',
                'bqr.customer_id',
                'bqr.parent_duplicate_quote_id',
                'bqr.renewal_import_code',
                'bqr.kyc_decision',
                'bqr.stale_at',
                DB::raw('("'.CustomerTypeEnum::Entity.'") as customer_type'),
                'c.insured_first_name',
                'c.insured_last_name',
                'c.emirates_id_number',
                'c.emirates_id_expiry_date',
                'c.receive_marketing_updates',
                'qrem.entity_id',
                'ent.code as entity_code',
                'ent.trade_license_no',
                'ent.company_name',
                'ent.company_address',
                'bqr.risk_score',
                'qrem.entity_type_code',
                'ent.industry_type_code',
                'ent.emirate_of_registration_id',
                'bqr.insurance_provider_id',
                'bqr.insurer_quote_number',
                'bqr.price_vat_applicable',
                'bqr.price_vat_not_applicable',
                'bqr.price_with_vat',
                'bqr.company_name as business_company_name',
                DB::raw('DATE_FORMAT(bqr.transaction_approved_at, "%d-%m-%Y %H:%i:%s") as transaction_approved_at'),
                'ent.emirate_of_registration_id',
                'bqr.price_vat_applicable',
                'bqr.vat',
                'bqr.insurer_quote_number',
                'bqr.policy_issuance_status_id',
                'bqr.policy_issuance_status_other',
                'bqr.policy_booking_date',
                'policy_start_date',
                'policy_issuance_date',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                'ps.text AS payment_status_id_text',
                'bqr.payment_status_id',
                'bqr.insly_migrated',
                'bqr.aml_status',
            )
            ->leftJoin('payments as py', 'py.code', '=', 'bqr.code')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'bqr.payment_status_id')
            ->leftJoin('business_type_of_insurance as bti', 'bti.id', '=', 'bqr.business_type_of_insurance_id')
            ->leftJoin('business_quote_request_detail as bqrd', 'bqrd.business_quote_request_id', '=', 'bqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'bqrd.lost_reason_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'bqr.transaction_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'bqr.advisor_id')
            ->leftJoin('users as uadv', 'uadv.id', '=', 'bqr.previous_advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'bqr.quote_status_id')
            ->leftJoin('customer as c', 'bqr.customer_id', 'c.id')
            ->leftJoin('renewal_batches as rb', 'bqr.renewal_batch_id', '=', 'rb.id')
            ->leftJoin('quote_request_entity_mapping as qrem', function ($entityMappingJoin) {
                $entityMappingJoin->on('qrem.quote_type_id', '=', DB::raw(QuoteTypeId::Business));
                $entityMappingJoin->on('qrem.quote_request_id', '=', 'bqr.id');
            })
            ->leftJoin('entities as ent', 'qrem.entity_id', '=', 'ent.id');
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

    public function getEntityPlain($id)
    {
        return BusinessQuote::where('id', $id)->with([
            'payments' => function ($payment) {
                $payment->with([
                    'paymentSplits' => function ($paymentSplit) {
                        $paymentSplit->with([
                            'paymentStatus',
                            'paymentMethod',
                            'documents',
                            'verifiedByUser',
                            'processJob',
                        ]);
                        $paymentSplit->orderBy('sr_no');
                    },
                ]);
                $payment->orderBy('created_at');
            },
        ])->first();
    }

    public function getDetailEntity($id)
    {
        return BusinessQuoteRequestDetail::firstOrCreate(['business_quote_request_id' => $id]);
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
            'companyAddress' => $request->company_address,
            'gender' => $request->gender,
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

        if (! isset($request->code) && ! isset($request->advisor_assigned_date) && ! isset($request->last_modified_date) && ! isset($request->email) && ! isset($request->mobile_no) && ! isset($request->created_at_start) && ! isset($request->payment_due_date) && ! isset($request->booking_date)
        && ! isset($request->company_name) && ! isset($request->insurer_tax_invoice_number) && ! isset($request->insurer_commission_tax_invoice_number)
        && ! isset($request->policy_expiry_date) && ! isset($request->policy_expiry_date_end)
        ) {
            $this->query->whereBetween('bqr.created_at', [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()]);
        }
        // if ($request->ajax()) {
        if (
            empty($request->email) && empty($request->code) && empty($request->first_name) &&
            empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no)
        ) {
            $this->query->where('bqr.quote_status_id', '!=', QuoteStatusEnum::Fake);
        }

        if (isset($request->last_modified_date) && $request->last_modified_date != '') {
            $dateArray = $request['last_modified_date'];

            $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
            $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('bqr.updated_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->advisor_assigned_date) && $request->advisor_assigned_date != '') {
            $dateArray = $request['advisor_assigned_date'];
            $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('bqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }

        if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
            $dateFrom = $this->parseDate($request['assigned_to_date_start'], true);
            $dateTo = $this->parseDate($request['assigned_to_date_end'], false);
            $this->query->whereBetween('bqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->next_followup_date) && $request->next_followup_date != '') {
            $dateFrom = $this->parseDate($request['next_followup_date'], true);
            $dateTo = $this->parseDate($request['next_followup_date_end'], true);
            $this->query->whereBetween('bqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->company_name)) {
            $this->query->where('ent.company_name', 'like', '%'.$request->company_name.'%');
        }

        if (
            in_array('created_at', $searchProperties)
            && isset($request->created_at_start) && $request->created_at_start != ''
            && empty($request->email)
            && empty($request->code)
            && empty($request->renewal_batches)
            && empty($request->quote_batch_id)
            && empty($request->payment_due_date)
            && empty($request->booking_date)
            && ! isset($request->previous_quote_policy_number)
            && ! isset($request->insurer_tax_invoice_number)
            && ! isset($request->insurer_commission_tax_invoice_number)
            && ! isset($request->policy_expiry_date)
            && ! isset($request->policy_expiry_date_end)
        ) {
            $dateFrom = Carbon::parse($request['created_at_start'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::parse($request['created_at_end'])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('bqr.created_at', [$dateFrom, $dateTo]);
        }
        if (isset($request->policy_expiry_date) && $request->policy_expiry_date != '' && isset($request->policy_expiry_date_end) && $request->policy_expiry_date_end != '') {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['policy_expiry_date']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['policy_expiry_date_end']));
            $this->query->whereBetween('bqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::CORPLINE) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::Business) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::GM)) {
            // if user has advisor Role then fetch leads assigned to the user only
            $this->query->where('bqr.advisor_id', Auth::user()->id); // fetch leads assigned to the user
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
        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $this->query->where('bqr.mobile_no', $request->mobile_no);
        }
        if (isset($request->policy_number) && $request->policy_number != '') {
            $this->query->where('bqr.policy_number', $request->policy_number);
        }
        if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
            $this->query->where(function ($query) use ($request) {
                $query->where('bqr.policy_number', $request->previous_quote_policy_number)
                    ->orWhere('bqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            });
        }
        if (isset($request->renewal_batches) && count($request->renewal_batches) != 0) {
            $this->query->whereIn('bqr.renewal_batch_id', $request->renewal_batches);
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

        // payment_status_id filter
        if (isset($request->payment_status) && is_array($request->payment_status) && count($request->payment_status) > 0) {
            $this->query->whereIn('bqr.payment_status_id', $request->payment_status);
        }

        // is_cold filter
        if (isset($request->is_cold) && $request->is_cold != '') {
            $this->query->where('bqr.is_cold', 1);
        }

        // is_stale filter
        if (isset($request->is_stale) && $request->is_stale != '') {
            $this->query->whereNotNull('bqr.stale_at');
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && $request->has('insurer_tax_invoice_number')) {
            $this->query->where('py.insurer_tax_number', $request->insurer_tax_invoice_number);
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && $request->has('insurer_commission_tax_invoice_number')) {
            $this->query->where('py.insurer_commmission_invoice_number', $request->insurer_commission_tax_invoice_number);
        }

        foreach ($searchProperties as $item) {
            if (! empty($request[$item]) && $item != 'created_at' && $item != 'company_name') {
                if ($request[$item] == 'null') {
                    $this->query->whereNull($item);
                } elseif ($item == 'advisor_id' && is_array($request[$item]) && ! empty($request[$item])) {
                    if ($request[$item][0] == 'null') {
                        $this->query->whereNull('advisor_id');
                    } else {
                        $this->query->whereIn('advisor_id', $request[$item]);
                    }
                } elseif ($item == 'business_type_of_insurance_id' && is_array($request[$item]) && ! empty($request[$item])) {
                    $this->query->whereIn('bqr.business_type_of_insurance_id', $request[$item]);
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

        $this->adjustQueryByDateFilters($this->query, 'bqr');

        // sortBy filter
        if (isset($request->sortBy) && $request->sortBy != '' && in_array(strtolower($request->sortType), ['asc', 'desc'])) {
            return $this->query->where('bti.text', '!=', 'Group Medical')->orderBy($request->sortBy, $request->sortType);
        } else {
            return $this->query->where('bti.text', '!=', 'Group Medical')->orderBy('bqr.created_at', 'DESC');
        }
    }

    private function parseDate($date, $isStartOfDay)
    {
        if ($date != '') {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            if ($isStartOfDay) {
                return Carbon::createFromFormat($dateFormat, $date)->startOfDay()->toDateString();
            } else {
                return Carbon::createFromFormat($dateFormat, $date)->endOfDay()->toDateString();
            }
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
            $businessQuote->company_address = $request->company_address;
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
            'company_name' => 'input|text|max:250',
            'company_address' => 'input|text|max:1000',
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
            'policy_expiry_date' => 'input|date|title|range',
            'renewal_batches' => 'select|title|multiple',
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
                $title = 'Ref-ID';
                break;
            case 'advisor_id':
                $title = 'Advisor';
                break;
            case 'quote_status_id':
                $title = 'Lead Status';
                break;
            case 'previous_quote_id':
                $title = 'Previous Quote Id';
                break;
            case 'policy_expiry_date':
                $title = 'Expiry Date';
                break;
            case 'previous_quote_policy_number':
                $title = 'Previous Policy Number';
                break;
            case 'previous_policy_expiry_date':
                $title = 'Previous Policy Expiry Date';
                break;
            case 'previous_quote_policy_premium':
                $title = 'Previous Policy Price';
                break;
            case 'premium':
                $title = 'Premium';
                break;
            case 'parent_duplicate_quote_id':
                $title = 'Parent Ref-ID';
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
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,email,mobile_no,brief_details,dob,policy_expiry_date,next_followup_date,renewal_import_code,device',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'show' => 'is_renewal,previous_quote_id,quote_status_id',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'company_name', 'business_type_of_insurance_id', 'advisor_id'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'renewal_batch', 'previous_quote_policy_number', 'previous_policy_expiry_date', 'previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            'create' => 'parent_duplicate_quote_id,gender,previous_quote_policy_premium,policy_expiry_date,previous_policy_expiry_date,policy_number,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,premium,source,transapp_code,renewal_import_code,device',
            'list' => 'parent_duplicate_quote_id,gender,policy_expiry_date,policy_number,is_renewal,previous_quote_id,email,mobile_no,brief_details,dob,next_followup_date,lost_reason,source,transapp_code,business_type_of_insurance_id,number_of_employees,renewal_import_code,device',
            'update' => 'parent_duplicate_quote_id,gender,previous_quote_policy_premium,policy_expiry_date,previous_policy_expiry_date,policy_number,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,updated_at,created_at,next_followup_date,lost_reason,source,transapp_code,renewal_import_code,device',
            'show' => 'gender,policy_expiry_date,is_renewal,policy_number,previous_quote_id',
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number', 'advisor_id'];
        $model->newBusinessSkipProperties = [
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,previous_policy_expiry_date,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,premium,lead_type_id,policy_expiry_date,previous_quote_id,renewal_import_code,device',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
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
        $quoteBatch = QuoteBatches::latest()->first();

        Log::info('Leads ids to assign: '.json_encode($leadsIds).' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);
        $result = [];
        foreach ($leadsIds as $leadId) {
            $lead = $this->getEntityPlain($leadId);

            $this->handleAssignment($lead, $userId, $quoteBatch, QuoteTypes::BUSINESS, BusinessQuoteRequestDetail::class, 'business_quote_request_id');
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
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
                return 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
            }
        }
        if ($userId == '' || $userId == null) {
            return 'Please select user to assign leads';
        }

        return 'true';
    }
}
