<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\DatabaseColumnsString;
use App\Enums\DefaultAdvisorEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\LeadSourceTypes;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Ken;
use App\Jobs\GetQuotePlansJob;
use App\Jobs\IntroEmailJob;
use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Models\HealthMemberDetail;
use App\Models\HealthPlan;
use App\Models\HealthQuote;
use App\Models\HealthQuotePlan;
use App\Models\HealthQuoteRequestDetail;
use App\Models\InsuranceProvider;
use App\Models\PaymentAction;
use App\Models\QuoteBatches;
use App\Models\QuoteType;
use App\Models\RenewalBatch;
use App\Models\Team;
use App\Models\User;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\GetUserTreeTrait;
use App\Traits\RolePermissionConditions;
use Auth;
use Carbon\Carbon;
use Hidehalo\Nanoid\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class HealthQuoteService extends BaseService
{
    protected $query;
    protected $leadAllocationService;
    protected $httpService;

    use AddPremiumAllLobs, GenericQueriesAllLobs, GetUserTreeTrait, RolePermissionConditions;

    public function __construct(HttpRequestService $httpService, LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
        $this->httpService = $httpService;
        $this->query = DB::table('health_quote_request as hqr')->select(
            'hqr.id',
            // 'hqr.prefill_plan_id',
            'hqr.uuid',
            'hqr.code',
            'hqr.first_name',
            DB::raw('DATE_FORMAT(hqr.created_at, "%d-%b-%Y %H:%i:%s") as created_at'),
            DB::raw('DATE_FORMAT(hqr.updated_at, "%d-%b-%Y %H:%i:%s") as updated_at'),
            DB::raw('DATE_FORMAT(hqr.payment_paid_at, "%d-%m-%Y %H:%i:%s") as payment_paid_at'),
            DB::raw('DATE_FORMAT(hqr.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
            'hqr.last_name',
            'hqr.payment_status_id',
            // 'hqr.email',
            // 'hqr.mobile_no',
            'hqr.preference',
            'hqr.details',
            'hqr.source',
            'hqr.additional_notes',
            DB::raw('DATE_FORMAT(hqr.dob, "%d-%m-%Y") as dob'),
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
            'hqr.previous_advisor_id',
            'u.name as advisor_id_text',
            'u.email as advisor_email',
            'u.mobile_no as advisor_mobile_no',
            'u.landline_no as advisor_landline_no',
            'uadv.name AS previous_advisor_id_text',
            'hqrd.next_followup_date',
            'hqrd.transapp_code',
            'hqrd.notes',
            'hqrd.insly_id',
            'hqr.lead_type_id',
            'lt.TEXT AS lead_type_id_text',
            'ls.text as lost_reason',
            'ls.id as lost_reason_id',
            'hqr.previous_quote_id',
            'hqr.salary_band_id',
            'sb.text as salary_band_id_text',
            'hqr.member_category_id',
            'mc.text as member_category_id_text',
            'hqr.policy_expiry_date',
            'hqr.renewal_batch',
            'rb.name as renewal_batch_text',
            'hqr.renewal_import_code',
            'hqr.previous_quote_policy_number',
            DB::raw('DATE_FORMAT(hqr.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
            'hqr.previous_quote_policy_premium',
            'hqr.device',
            'hqr.wcu_id',
            'wcu.name as wcu_id_text',
            'hqr.plan_id',
            'hqr.policy_start_date',
            'hqr.policy_issuance_date',
            'hqr.customer_id',
            'hqr.currently_insured_with_id',
            'ins_provider.TEXT as currently_insured_with_id_text',
            'hqr.parent_duplicate_quote_id',
            'hqr.is_ecommerce',
            'payment_status.text as payment_status_text',
            'hqr.price_starting_from',
            'lu.text as transaction_type_text',
            'hqr.kyc_decision',
            'hqr.risk_score',
            'hqr.enquiry_count',
            'hqr.policy_booking_date',
            DB::raw('IF(EXISTS (
                SELECT *
                FROM quote_request_entity_mapping
                WHERE quote_type_id = '.QuoteTypeId::Health.' AND quote_request_id = hqr.id),
                "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
            as customer_type'),
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
            'qrem.entity_type_code',
            'ent.industry_type_code',
            'ent.emirate_of_registration_id',
            DB::raw('(CASE
                WHEN hqr.assignment_type = 1 THEN "System Assigned"
                WHEN hqr.assignment_type = 2 THEN "System ReAssigned"
                WHEN hqr.assignment_type = 3 THEN "Manual Assigned"
                WHEN hqr.assignment_type = 4 THEN "Manual ReAssigned"
                WHEN hqr.assignment_type = 5 THEN "Bought Lead"
                WHEN hqr.assignment_type = 6 THEN "ReAssigned as Bought Lead" ELSE "" END) as assignment_type'),
            'ihp.code as plan_provider_code',
            'ihp.code as plan_provider_code',
            'hqr.health_plan_co_payment_id',
            'hp.text as health_plan_name_text',
            'hp.plan_type_id as plan_type_id',
            'ihp.text as plan_provider_name_text',
            'hqr.health_plan_type_id',
            'hqr.price_vat_not_applicable',
            'hqr.price_vat_applicable',
            'hqr.price_with_vat',
            'hqr.vat',
            'hqr.insurer_quote_number',
            'hqr.policy_issuance_status_id',
            'hqr.policy_issuance_status_other',
            'hqr.stale_at',
            DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
            DB::raw('DATE_FORMAT(hqr.transaction_approved_at, "%d-%m-%Y %H:%i:%s") as transaction_approved_at'),
            'hqr.insly_migrated',
            'hqr.sic_advisor_requested',
            'hqr.aml_status',
            'hqr.insurance_provider_id'
        )
            ->leftJoin('payments as py', 'py.code', '=', 'hqr.code')
            ->leftJoin('marital_status as ms', 'ms.id', '=', 'hqr.marital_status_id')
            ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'hqrd.lost_reason_id')
            ->leftJoin('health_cover_for as hcf', 'hcf.id', '=', 'hqr.cover_for_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'hqr.nationality_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'hqr.transaction_type_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'hqr.emirate_of_your_visa_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('health_lead_type as lt', 'lt.id', '=', 'hqr.lead_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
            ->leftJoin('users as uadv', 'uadv.id', '=', 'hqr.previous_advisor_id')
            ->leftJoin('users as wcu', 'wcu.id', '=', 'hqr.wcu_id')
            ->leftJoin('salary_band as sb', 'sb.id', '=', 'hqr.salary_band_id')
            ->leftJoin('health_plan as hp', 'hp.id', '=', 'hqr.plan_id')
            ->leftJoin('insurance_provider as ihp', 'ihp.id', '=', 'hp.provider_id')
            ->leftJoin('member_category as mc', 'mc.id', '=', 'hqr.member_category_id')
            ->leftJoin('insurance_provider as ins_provider', 'ins_provider.id', '=', 'hqr.currently_insured_with_id')
            ->leftjoin('payment_status', 'hqr.payment_status_id', 'payment_status.id')
            ->leftJoin('customer as c', 'hqr.customer_id', 'c.id')
            ->leftJoin('renewal_batches as rb', 'hqr.renewal_batch_id', '=', 'rb.id')
            ->leftJoin('quote_request_entity_mapping as qrem', function ($entityMappingJoin) {
                $entityMappingJoin->on('qrem.quote_type_id', '=', DB::raw(QuoteTypeId::Health));
                $entityMappingJoin->on('qrem.quote_request_id', '=', 'hqr.id');
            })
            ->leftJoin('entities as ent', 'qrem.entity_id', '=', 'ent.id');
    }

    public function getEntity($id)
    {
        return $this->query->addSelect(['hqr.email', 'hqr.mobile_no'])->where('hqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return HealthQuote::where('id', $id)->with([
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
            'plan',
        ])->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = HealthQuoteRequestDetail::where('health_quote_request_id', $id)->first();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }

        return $lostId;
    }

    public function getDetailEntity($id)
    {
        return HealthQuoteRequestDetail::firstOrCreate(
            ['health_quote_request_id' => $id],
        );
    }

    public function getLeadsForAssignment()
    {
        return HealthQuote::orderBy('created_at', 'desc')->get();
    }

    public function saveHealthQuote(Request $request)
    {
        $sourceName = $request->is_ebp_renewal == 'on' ? LeadSourceTypes::EBPRENEWALS : config('constants.SOURCE_NAME');
        $dataArr = [
            'email' => $request->email,
            'details' => $request->details,
            'mobileNo' => $request->mobile_no,
            'preference' => $request->preference,
            'source' => $sourceName,
            'maritalStatusId' => $request->marital_status_id,
            'premium' => $request->premium,
            'leadTypeId' => $request->lead_type_id,
            'referenceUrl' => config('constants.APP_URL'),
            'is_ebp_renewal' => $request->is_ebp_renewal == 'on' ? true : false,
            'coverForId' => $request->cover_for_id,
            'hasDental' => $request->has_dental == 'on' ? true : false,
            'hasWorldwideCover' => $request->has_worldwide_cover == 'on' ? true : false,
            'hasHome' => $request->has_home == 'on' ? true : false,
            'currentlyInsuredWithId' => $request->currently_insured_with_id,
            'healthPlanTypeId' => $request->plan_type_id,
        ];
        $dataArr['memberDetails'][] = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'dob' => $request->dob,
            'gender' => $request->gender,
            'nationalityId' => $request->nationality_id,
            'emirateOfYourVisaId' => $request->emirate_of_your_visa_id,
            'salaryBandId' => $request->salary_band_id,
            'memberCategoryId' => $request->member_category_id,
        ];
        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-health-quote', $dataArr, HealthQuote::class);

        if (isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::HealthQuote, $request, $response);
            $subTeam = null;
            if (auth()->user()->subTeam) {
                $subTeam = auth()->user()->subTeam->name;
            }
            HealthQuote::where('uuid', $response->quoteUID)->update(['health_team_type' => $subTeam]);
        }

        return $response;
    }

    public function getGridData($model = null, $request = null)
    {
        $searchProperties = [];
        $isRenewalUser = Auth::user()->isRenewalUser();
        $isRenewalAdvisor = Auth::user()->isRenewalAdvisor();
        $isRenewalManager = Auth::user()->isRenewalManager();
        $isNewManager = Auth::user()->isNewBusinessManager();
        $isNewAdvisor = Auth::user()->isNewBusinessAdvisor();
        if ($model != null) {
            if ($isRenewalUser || $isRenewalManager || $isRenewalAdvisor) {
                $searchProperties = $model->renewalSearchProperties;
            } elseif ($isNewManager || $isNewAdvisor) {
                $searchProperties = $model->newBusinessSearchProperties;
            } else {
                $searchProperties = $model->searchProperties;
            }
        } else {
            $searchProperties = $this->fillModelSearchProperties();
            $request = request();
        }

        if (
            empty($request->email) && empty($request->code) && empty($request->first_name) &&
            empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no)
        ) {
            $this->query->where('hqr.quote_status_id', '!=', QuoteStatusEnum::Fake);
        }
        if (! empty($request->assigned_to_date_start) && ! empty($request->assigned_to_date_end)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['assigned_to_date_start']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['assigned_to_date_end']));

            $this->query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            $this->query->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        }
        if (isset($request->next_followup_date) && $request->next_followup_date != '') {
            $dateFrom = $this->parseDate($request['next_followup_date'], true);
            $dateTo = $this->parseDate($request['next_followup_date_end'], true);
            $this->query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->transaction_approved_dates)) {
            $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
            $startDate = Carbon::parse($request->transaction_approved_dates[0])->startOfDay()->format($dateFormat);
            $endDate = Carbon::parse($request->transaction_approved_dates[1])->endOfDay()->format($dateFormat);
            $this->query->whereBetween('hqr.transaction_approved_at', [$startDate, $endDate]);
        }
        if (! isset($request->code) && ! isset($request->last_modified_date) && ! isset($request->email) && ! isset($request->mobile_no) && ! isset($request->created_at_start) && ! isset($request->payment_due_date)
        && ! isset($request->booking_date) && ! isset($request->renewal_batches)
    && ! isset($request->previous_quote_policy_number) && ! isset($request->transaction_approved_dates) && ! isset($request->insurer_tax_invoice_number) && ! isset($request->insurer_commission_tax_invoice_number)) {
            $this->query->whereBetween('hqr.created_at', [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()]);
        }
        if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
            $dateFrom = $this->parseDate($request['created_at'], true);
            $dateTo = $this->parseDate($request['created_at_end'], true);
            $this->query->whereBetween('hqr.created_at', [$dateFrom, $dateTo]);
        }
        if (isset($request->policy_expiry_date) && $request->policy_expiry_date != '' && isset($request->policy_expiry_date_end) && $request->policy_expiry_date_end != '') {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['policy_expiry_date']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['policy_expiry_date_end']));
            $this->query->whereBetween('hqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }

        if (isset($request->last_modified_date) && $request->last_modified_date != '') {
            $dateArray = $request['last_modified_date'];

            $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
            $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('hqr.updated_at', [$dateFrom, $dateTo]);
        }

        if (
            ! empty($request->created_at_start)
            && ! empty($request->created_at_end)
            && empty($request->code)
            && empty($request->email)
            && empty($request->mobile_no)
            && empty($request->payment_due_date)
            && empty($request->booking_date)
            && empty($request->renewal_batches)
            && empty($request->previous_quote_policy_number)
            && ! isset($request->insurer_tax_invoice_number)
            && ! isset($request->insurer_commission_tax_invoice_number)
        ) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['created_at_start']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['created_at_end']));

            $this->query->whereBetween('hqr.created_at', [$dateFrom, $dateTo]);
        }
        if (Auth::user()->isSpecificTeamAdvisor('Health') || Auth::user()->isSpecificTeamAdvisor('EBP') || Auth::user()->isSpecificTeamAdvisor('RM')) {
            // if user has advisor Role then fetch leads assigned to the user only
            $this->query->where('hqr.advisor_id', Auth::user()->id); // fetch leads assigned to the user
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
            $this->query->where(function ($query) use ($request) {
                $query->where('hqr.policy_number', $request->previous_quote_policy_number)
                    ->orWhere('hqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            });
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('hqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->renewal_batches) && count($request->renewal_batches) != 0) {
            $this->query->whereIn('hqr.renewal_batch_id', $request->renewal_batches);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $this->query->where('hqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        $this->whereBasedOnRole($this->query, 'hqr', quoteTypeCode::Health);

        if (! isset($request->email) && $request->email == '') {
            $this->query->where('hqr.quote_status_id', '!=', 9);
        }

        if (isset($request->next_followup_date) && $request->next_followup_date != '') {
            $dateFrom = $this->parseDate($request['next_followup_date'], true);
            $dateTo = $this->parseDate($request['next_followup_date_end'], true);
            $this->query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        // health_team_type filter
        if (isset($request->sub_team) && $request->sub_team != '') {
            $this->query->where('hqr.health_team_type', $request->sub_team);
        }
        // quote_status filter
        if (isset($request->quote_status) && is_array($request->quote_status) && count($request->quote_status) > 0) {
            $this->query->whereIn('quote_status_id', $request->quote_status);
        }

        if (isset($request->advisors) && is_array($request->advisors) && in_array(DefaultAdvisorEnum::UNASSIGNED, $request->advisors)) {
            $this->query->whereNull('hqr.advisor_id');
        }

        // advisors filter
        if (isset($request->advisors) && is_array($request->advisors) && count($request->advisors) > 0 && ! in_array(DefaultAdvisorEnum::UNASSIGNED, $request->advisors)) {
            $this->query->whereIn('advisor_id', $request->advisors);
        }
        // is_renewal filter
        if (isset($request->is_renewal) && $request->is_renewal != '') {
            if ($request->is_renewal == 'Yes') {
                $this->query->whereNotNull('previous_quote_policy_number');
            } else {
                $this->query->whereNull('previous_quote_policy_number');
            }
        }

        // payment_status_id filter
        if (isset($request->payment_status) && is_array($request->payment_status) && count($request->payment_status) > 0) {
            $this->query->whereIn('hqr.payment_status_id', $request->payment_status);
        }

        // is_cold filter
        if (isset($request->is_cold) && $request->is_cold != '') {
            $this->query->where('hqr.is_cold', 1);
        }

        // is_stale filter
        if (isset($request->is_stale) && $request->is_stale != '') {
            $this->query->whereNotNull('hqr.stale_at');
        }

        if (Auth::user()->isSpecificTeamAdvisor('Health') || Auth::user()->isSpecificTeamAdvisor('EBP') || Auth::user()->isSpecificTeamAdvisor('RM')) {
            // if user has advisor Role then fetch leads assigned to the user only
            $this->query->where('hqr.advisor_id', Auth::user()->id); // fetch leads assigned to the user
        }
        if (isset($request->assignment_type) && ! empty($request->assignment_type) && $request->assignment_type !== 'all') {
            $this->query->where('hqr.assignment_type', $request->assignment_type);
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

        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('hqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->renewal_batches) && count($request->renewal_batches) != 0) {
            $this->query->whereIn('hqr.renewal_batch_id', $request->renewal_batches);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $this->query->where('hqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        $this->whereBasedOnRole($this->query, 'hqr', quoteTypeCode::Health);

        if (isset($request->is_renewal) && $request->is_renewal != '') {
            if ($request->is_renewal == quoteTypeCode::yesText) {
                $this->query->whereNotNull('hqr.previous_quote_policy_number');
            }
            if ($request->is_renewal == quoteTypeCode::noText) {
                $this->query->whereNull('hqr.previous_quote_policy_number');
            }
        }
        if (isset($request->sic_advisor_requested) && $request->sic_advisor_requested != 'All') {
            $this->query->where('hqr.sic_advisor_requested', $request->sic_advisor_requested);
        }
        if (isset($request->is_ecommerce)) {
            $isEcommerce = $request->is_ecommerce == 'Yes' ? 1 : 0;
            $this->query->where('hqr.is_ecommerce', $isEcommerce);
        }
        if ($request->has('segment_filter')) {
            $segmentFilter = $request->input('segment_filter');
            $subQueryCallback = function ($subQuery) {
                $subQuery->distinct()
                    ->select('quote_uuid')
                    ->from('quote_tags')
                    ->where('name', QuoteSegmentEnum::SIC->tag())
                    ->where('quote_type_id', QuoteTypeId::Health);
            };
            $this->query->when($segmentFilter === QuoteSegmentEnum::SIC->value, function ($query) use ($subQueryCallback) {
                $query->whereIn('hqr.uuid', $subQueryCallback);
            })->when($segmentFilter === QuoteSegmentEnum::NON_SIC->value, function ($query) use ($subQueryCallback) {
                $query->whereNotIn('hqr.uuid', $subQueryCallback);
            });
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && $request->has('insurer_tax_invoice_number')) {
            $this->query->where('py.insurer_tax_number', $request->insurer_tax_invoice_number);
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && $request->has('insurer_commission_tax_invoice_number')) {
            $this->query->where('py.insurer_commmission_invoice_number', $request->insurer_commission_tax_invoice_number);
        }

        $this->adjustQueryByDateFilters($this->query, 'hqr');

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
                    $skipped = ['is_ecommerce', 'is_renewal', 'previous_policy_expiry_date', 'next_followup_date'];
                    if (in_array($item, $skipped)) {
                        continue;
                    }
                    $this->query->where($this->getQuerySuffix($item).'.'.$item, $request[$item]);
                }
            }
        }

        // sortBy filter
        if (isset($request->sortBy) && $request->sortBy != '') {
            return $this->query->orderBy($request->sortBy, $request->sortType);
        } else {
            return $this->query->orderBy('hqr.created_at', 'DESC');
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
            default:
                return 'hqr';
                break;
        }
    }

    public function updateHealthQuote(Request $request, $id)
    {

        $healthQuote = HealthQuote::where('uuid', $id)->first();
        $sourceName = $request->is_ebp_renewal == 'on' ? LeadSourceTypes::EBPRENEWALS : $healthQuote->source;
        $healthQuote->first_name = $request->first_name;
        $healthQuote->last_name = $request->last_name;
        $healthQuote->details = $request->details;
        $healthQuote->preference = $request->preference;
        $healthQuote->source = $sourceName;
        $healthQuote->marital_status_id = $request->marital_status_id;
        $healthQuote->cover_for_id = $request->cover_for_id;
        $healthQuote->nationality_id = $request->nationality_id;
        $healthQuote->is_ebp_renewal = $request->is_ebp_renewal == 'on' ? true : false;
        $healthQuote->has_dental = $request->has_dental == 'on' ? true : false;
        $healthQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? true : false;
        $healthQuote->has_home = $request->has_home == 'on' ? true : false;
        $healthQuote->premium = $request->premium;
        // check if salary band ,member category ,gender or emirates of your visa is updated we need to update quote_updated_at for latest ratings
        if ($healthQuote->salary_band_id != $request->salary_band_id || $healthQuote->member_category_id != $request->member_category_id || $healthQuote->emirate_of_your_visa_id != $request->emirate_of_your_visa_id || $healthQuote->gender != $request->gender || $healthQuote->currently_insured_with_id != $request->currently_insured_with_id || $healthQuote->dob != $request->dob) {
            $healthQuote->quote_updated_at = Carbon::now();
            $updateMemberDetails = [
                'member_category_id' => $request->member_category_id,
                'salary_band_id' => $request->salary_band_id,
                'gender' => $request->gender,
                'dob' => $request->dob,
            ];

            $healthQuoteFirstMember = HealthMemberDetail::where('health_quote_request_id', $healthQuote->id)->first();
            if ($healthQuoteFirstMember) {
                $healthQuoteFirstMember->update(array_merge(
                    $updateMemberDetails,
                    [
                        'nationality_id' => $request->nationality_id,
                        'emirate_of_your_visa_id' => $request->emirate_of_your_visa_id,
                    ]
                ));
            }

            if ($healthQuote->primary_member_id) {
                $healthQuote->memberDetails()->update($updateMemberDetails);
            }
        }
        $healthQuote->salary_band_id = $request->salary_band_id;
        $healthQuote->member_category_id = $request->member_category_id;
        $healthQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $healthQuote->currently_insured_with_id = $request->currently_insured_with_id;
        $healthQuote->gender = $request->gender;
        $healthQuote->dob = $request->dob;
        $healthQuote->policy_start_date = $request->policy_start_date;
        $healthQuote->health_plan_type_id = $request->plan_type_id;
        $healthQuote->save();

        if (isset($request->return_to_view)) {
            return redirect('quote/health/'.$id)->with('success', 'Health Quote has been updated');
        }
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
        if (! empty($CDBID)) {
            $query->where('hqr.id', '=', $CDBID);
        }
        if (! empty($email)) {
            $query->where('hqr.email', '=', $email);
        }
        if (! empty($mobile_no)) {
            $query->where('hqr.mobile_no', '=', $mobile_no);
        }

        return $query;
    }

    public function updateChildRecord($id, $advisorId)
    {
        $childRecord = HealthQuoteRequestDetail::where('health_quote_request_id', $id)->first();
        $oldAdvisorAssignedDate = $childRecord->advisor_assigned_date ?? null;
        $data = [];
        if ($advisorId != null) {
            $data = [
                'advisor_assigned_date' => now(),
                'advisor_assigned_by_id' => auth()->user()->id,
            ];
        }

        HealthQuoteRequestDetail::updateOrCreate(
            ['health_quote_request_id' => $id],
            $data
        );

        return $oldAdvisorAssignedDate;
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
            'quote_status_id' => 'select|title|multiple',
            'advisor_id' => 'select|title|multiple',
            'wcu_id' => 'select|title',
            'created_at' => 'input|date|title|range',
            'updated_at' => 'input|date|title',
            'dob' => 'input|date|title|required',
            'health_team_type' => '|static|default:All|All,Good,Best,Entry-Level,Wow-Call,No-Type',
            'next_followup_date' => 'input|date|title|range',
            'transapp_code' => 'readonly|none',
            'lost_reason' => 'input|text',
            'premium' => 'input|number',
            'policy_number' => 'input|text',
            'preference' => 'input|text',
            'details' => 'input|text',
            'is_ebp_renewal' => 'input|checkbox|title',
            'source' => 'input|text|title',
            'marital_status_id' => 'select|title',
            'assignment_type' => 'input|title|none',
            'cover_for_id' => 'select|title|required',
            'nationality_id' => 'select|title|required',
            'lead_type_id' => 'select|title',
            'has_dental' => 'input|checkbox|title',
            'has_worldwide_cover' => 'input|checkbox|title',
            'has_home' => 'input|checkbox|title',
            'emirate_of_your_visa_id' => 'select|title|required',
            'previous_quote_id' => 'readonly|title',
            'policy_expiry_date' => 'input|date|title|range',
            'is_renewal' => '|static|'.GenericRequestEnum::Yes.','.GenericRequestEnum::No.'',
            'salary_band_id' => 'select|title',
            'member_category_id' => 'select|title',
            'gender' => '|static|'.GenericRequestEnum::MALE_SINGLE.','.GenericRequestEnum::FEMALE_SINGLE.','.GenericRequestEnum::FEMALE_MARRIED.'',
            'renewal_batches' => 'select|title|multiple',
            'renewal_import_code' => 'input|text',
            'previous_quote_policy_number' => 'input|title',
            'previous_policy_expiry_date' => 'input|date|title|range',
            'previous_quote_policy_premium' => 'input|title',
            'parent_duplicate_quote_id' => 'input|title',
            'currently_insured_with_id' => 'select|title',
            'device' => 'input|title',
            'is_ecommerce' => '|static|'.GenericRequestEnum::Yes.','.GenericRequestEnum::No.'',
            'policy_start_date' => 'input|date',
            'plan_type_id' => 'select|title',
        ];
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'is_ecommerce,policy_start_date,wcu_id,parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'list' => 'policy_start_date,parent_duplicate_quote_id,previous_policy_expiry_date,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,is_renewal,gender,previous_quote_id,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,next_followup_date,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,policy_expiry_date,renewal_import_code,device',
            'update' => 'premium,is_ecommerce,wcu_id,parent_duplicate_quote_id,previous_policy_expiry_date,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'show' => 'wcu_id,is_renewal,id,source,previous_quote_id,quote_status_id',
        ];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['is_ecommerce', 'created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'renewal_batch', 'previous_quote_policy_number', 'previous_policy_expiry_date', 'previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            'create' => 'is_ecommerce,policy_start_date,premium,wcu_id,parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'list' => 'policy_start_date,currently_insured_with_id,premium,parent_duplicate_quote_id,policy_number,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,policy_expiry_date,previous_quote_id,renewal_import_code,device',
            'update' => 'is_ecommerce,wcu_id,premium,parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'show' => 'source,currently_insured_with_id,wcu_id,premium,member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date,previous_quote_id,quote_status_id',
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['is_ecommerce', 'created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            'create' => 'is_ecommerce,policy_start_date,currently_insured_with_id,wcu_id,parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'list' => 'policy_start_date,currently_insured_with_id,parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,policy_expiry_date,previous_quote_id,renewal_import_code,device',
            'update' => 'is_ecommerce,currently_insured_with_id,wcu_id,parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,device',
            'show' => 'source,currently_insured_with_id,wcu_id,policy_expiry_date,member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date,previous_quote_id,quote_status_id',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'health_team_type', 'is_renewal', 'is_ecommerce', 'advisor_id'];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'marital_status_id':
                $title = 'Marital Status';
                break;
            case 'code':
                $title = 'Ref-ID';
                break;
            case 'cover_for_id':
                $title = 'Who would you like cover for?';
                break;
            case 'nationality_id':
                $title = 'Nationality';
                break;
            case 'is_ebp_renewal':
                $title = 'Is EBP Renewal';
                break;
            case 'mobile_no':
                $title = 'Mobile Number';
                break;
            case 'has_dental':
                $title = 'Dental';
                break;
            case 'has_worldwide_cover':
                $title = 'WorldWide Cover';
                break;
            case 'has_home':
                $title = 'Home Country Cover';
                break;
            case 'advisor_id':
                $title = 'Advisor';
                break;
            case 'wcu_id':
                $title = 'WC Advisor';
                break;
            case 'lead_type_id':
                $title = 'Lead Type';
                break;
            case 'source':
                $title = 'Source';
                break;
            case 'emirate_of_your_visa_id':
                $title = 'Emirate of your visa';
                break;
            case 'dob':
                $title = 'Date of Birth';
                break;
            case 'quote_status_id':
                $title = 'Lead Status';
                break;
            case 'previous_quote_id':
                $title = 'Previous Quote Id';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'health_team_type':
                $title = 'Health Team Type';
                break;
            case 'next_followup_date':
                $title = 'Next Followup Date';
                break;
            case 'salary_band_id':
                $title = 'Salary Band';
                break;
            case 'member_category_id':
                $title = 'Member Category';
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
            case 'currently_insured_with_id':
                $title = 'Currently Insured With';
                break;
            case 'parent_duplicate_quote_id':
                $title = 'Parent Ref-ID';
                break;
            case 'device':
                $title = 'Device';
                break;
            case 'assignment_type':
                $title = 'Assignment Type';
                break;
            case 'plan_type_id':
                $title = 'Plan Type';
                break;
            default:
                break;
        }

        return $title;
    }

    public function convertLeadToGM($lead)
    {
        $businessLead = new BusinessQuote;
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
        $businessLead->code = 'BUS-'.$uuid;
        $businessLead->customer_id = $lead->customer_id;
        $healthQuotePlan = HealthQuotePlan::where('health_quote_request_id', $lead->id)->first();
        if (isset($healthQuotePlan)) {
            $healthQuotePlan->health_quote_request_id = null;
            $healthQuotePlan->save();
        }
        $lead->primary_member_id = null;
        $lead->plan_id = null;
        $lead->save();
        $healthMemberIds = HealthMemberDetail::where('health_quote_request_id', $lead->id)->pluck('id');
        foreach ($healthMemberIds as $id) {
            HealthMemberDetail::findOrFail($id)->delete();
        }
        $businessLead->save();
        HealthQuote::find($lead->id)->delete();
    }

    public function generateUUID()
    {
        $client = new Client;
        $alphabets = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $nanoId = $client->formattedId($alphabets, 8);

        return $nanoId;
    }

    public function getDuplicateEntityByCode($code)
    {
        return HealthQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function getQuotePlans($id)
    {
        $quoteUuId = HealthQuote::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        $plansDataArr = [
            'quoteUID' => $quoteUuId,
            'lang' => 'en',
        ];

        $client = new \GuzzleHttp\Client;

        try {
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic '.$authBasic,
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
            } elseif (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } elseif (isset($response->msg)) {
                $responseBodyAsString = $response->msg;
            } else {
                $responseBodyAsString = 'Quote unavailable for the selected location and region. Please call 800 ALFRED.';
            }

            return $responseBodyAsString;
        }
    }

    public function getCoPayment($id)
    {
        $quoteUuId = HealthQuote::where('uuid', '=', $id)->first();
        $coPayment = DB::table('health_plan_co_payments as hpcp')->where('id', $quoteUuId->health_plan_co_payment_id)->first();

        return $coPayment;
    }

    public function getQuotePlansPriority($id)
    {
        $quoteUuId = HealthQuote::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans-order-priority';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        $plansDataArr = [
            'quoteUID' => $quoteUuId,
            'lang' => 'en',
        ];

        $client = new \GuzzleHttp\Client;

        try {
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic '.$authBasic,
                    ],
                    'body' => json_encode($plansDataArr),
                    'timeout' => $plansApiTimeout,
                ]
            );

            $getStatusCode = $kenRequest->getStatusCode();

            if ($getStatusCode == 200) {
                $getContents = $kenRequest->getBody();
                $getdecodeContents = json_decode($getContents);

                return $getdecodeContents->quote;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            if (isset($response->message)) {
                $responseBodyAsString = $response->message;
            } elseif (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } elseif (isset($response->msg)) {
                $responseBodyAsString = $response->msg;
            } else {
                $responseBodyAsString = 'Quote unavailable for the selected location and region. Please call 800 ALFRED.';
            }

            return $responseBodyAsString;
        }
    }

    public function getMembersDetail($id)
    {
        return HealthMemberDetail::where('health_quote_request_id', $id)->with('nationality', 'emirate', 'relation')->get();
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
        info('Leads ids to assign: '.json_encode($leadsIds));
        $userId = $request->assigned_to_id_new;
        $result = [];
        foreach ($leadsIds as $leadId) {
            $lead = $this->getEntityPlain($leadId);
            if ($this->isLeadTransactionApproved($lead) && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
                info('Cannot assign WCU as lead is in Transaction Approved state , lead id: '.$leadId);
                array_push($result, ['leadId' => $lead->code, 'msg' => 'Cannot assign WCU as lead is in Transaction Approved state']);

                continue;
            } elseif ($lead) {
                $lead->advisor_id = null;
                $lead->quote_status_id = QuoteStatusEnum::NewLead;
                $lead->wcu_id = $userId;
                $lead->health_team_type = $request->assign_team;
                $lead->save();
                info('WCU advisor : '.$userId.' assigned to lead: '.$leadId);
            }
        }

        return $result;
    }

    public function assignHealthTeam($request, $lead): bool
    {
        if ($this->isLeadTransactionApproved($lead) && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
            info('Cannot assign Health Team as lead is in Transaction Approved state');

            return false;
        }
        if ($lead->health_team_type != null && $lead->advisor_id != null) {
            info('Removing previous advisor as lead already assigned to a health team');
            $this->removePreviousAdvisorAndUpdateStatus($lead, QuoteStatusEnum::Qualified);
        }
        $selectedTeam = $request->get('assign_team');
        if ($selectedTeam == quoteTypeCode::GM) {
            info('Assigning lead to GM');
            $this->convertLeadToGM($lead);
            $lead->health_team_type = quoteTypeCode::GM;
        } else {
            info('Assigning lead to '.$selectedTeam.' team');
            $lead->health_team_type = $selectedTeam;
            if ($lead->quote_status_id == QuoteStatusEnum::Qualified) {
                $lead->wcu_id = null;
            }
        }
        $lead->quote_updated_at = Carbon::now();
        $lead->save();
        // check if team is assigned and status not qualified yet so mark it qualified.
        if ($lead && $lead->health_team_type && $lead->quote_status_id != QuoteStatusEnum::Qualified && auth()->user()->isHealthWCUAdvisor()) {
            HealthQuote::where('id', $lead->id)->update([
                'quote_status_id' => QuoteStatusEnum::Qualified,
                'quote_status_date' => now(),
            ]);
        }

        return true;
    }

    public function isLeadTransactionApproved($lead): bool
    {
        if ($lead != null && $lead->quote_status_id == QuoteStatusEnum::TransactionApproved) {
            return true;
        }

        return false;
    }

    public function processManualLeadAssignment($request): array
    {
        // Extract lead IDs from the request

        $sourceData = ($request->selectTmLeadId == '' || $request->selectTmLeadId === null) ? $request->entityId : $request->selectTmLeadId;
        $leadsIds = array_map('intval', explode(',', trim($sourceData, ',')));

        $userId = (int) $request->assigned_to_id_new;
        $quote_type = $request->modelType;
        $quoteBatch = QuoteBatches::latest()->first();

        foreach ($leadsIds as $leadId) {
            $lead = $this->getEntityPlain($leadId);

            if (isset($request->assign_team) && $request->assign_team !== '') {
                $lead->health_team_type = $request->assign_team;
            }

            $oldAssignmentType = $lead->assignment_type;

            $isReassignment = $lead->advisor_id != null ? true : false; // checking if the advisor is already assigned or not for reassignment email template

            $previousAdvisorId = $lead->advisor_id; // saving previous advisor before updating the new to update the counts

            $lead->advisor_id = $userId;

            $lead->assignment_type = $isReassignment ? AssignmentTypeEnum::MANUAL_REASSIGNED : AssignmentTypeEnum::MANUAL_ASSIGNED;
            // will update the car quote request detail entity about assignment
            $oldAdvisorAssignedDate = $this->updateChildRecord($lead->id, $userId);

            info('Manual assignment done and details table updated for lead : '.$lead->uuid.'and old advisor assigned date is : '.$oldAdvisorAssignedDate.' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);
            // update new and previous (if applicable) advisor counts in lead allocation table
            $this->addManualAllocationCountAndUpdate($userId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $oldAssignmentType, $quote_type);
            // update existing record of quote view count if exists and reset count to zero
            $this->addOrUpdateQuoteViewCount($lead, QuoteTypeId::Health, $userId);

            $lead->quote_batch_id = $quoteBatch->id;

            $lead->save();

            Haystack::build()
                ->addJob(new GetQuotePlansJob($lead))
                ->then(function () use ($lead, $isReassignment, $previousAdvisorId) {
                    if (in_array($lead->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED])) {
                        IntroEmailJob::dispatch(quoteTypeCode::Health, 'Capi', $lead->uuid, 'send-rm-intro-email', $previousAdvisorId, $isReassignment)->delay(now()->addSeconds(15));
                    }
                })->dispatch();
        }

        return [];
    }

    public function addManualAllocationCountAndUpdate($newAdvisorId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $quoteType = null)
    {
        // Check if $lead or $newAdvisorId is not provided
        if ($lead === null || $newAdvisorId === null) {
            return;
        }

        info('Previous assignment type is : '.$previousAssignmentType);

        // Constants for system assigned types
        $systemAssignedTypes = [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD];

        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType) ?? null;
        // Get the allocation record for the new advisor
        $newAdvisorAllocationRecord = $this->leadAllocationService->getLeadAllocationRecordByUserId($newAdvisorId, $quoteTypeId);

        // Update allocation counts for the new advisor (if applicable)
        $this->updateAllocationCountsForNewAdvisor($newAdvisorAllocationRecord, $lead, $systemAssignedTypes);

        // Get the allocation record for the previous advisor (if applicable)
        if ($previousAdvisorId !== null) {
            $previousAdvisorAllocationRecord = $this->leadAllocationService->getLeadAllocationRecordByUserId($previousAdvisorId, $quoteTypeId);

            // Update allocation counts for the previous advisor (if applicable)
            $this->updateAllocationCountsForPreviousAdvisor($previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $previousAdvisorAllocationRecord, $systemAssignedTypes);
        }
    }

    public function getEntityPlainByUUID($uuid)
    {
        return HealthQuote::where('uuid', $uuid)->first();
    }

    public function getEcomDetails($data)
    {
        $response['providerName'] = '';
        $response['network'] = '';
        $response['paymentStatus'] = '';
        $response['paidAt'] = '';
        $response['planName'] = '';
        $response['priceWithVAT'] = '';
        $response['priceWithLP'] = ''; // Premium with loading price

        $planData = HealthQuotePlan::where('health_quote_request_id', $data->id)->first();
        if ($planData) {
            $planPayload = json_decode($planData->plan_payload, true);
            if (isset($planPayload['plans'])) {
                foreach ($planPayload['plans'] as $plan) {
                    if ($plan['id'] == $data->plan_id) {
                        $response['providerName'] = $plan['providerName'];
                        $response['paymentStatus'] = GenericRequestEnum::NotApplicable;
                        $response['paidAt'] = GenericRequestEnum::NotApplicable;
                        $response['planName'] = $plan['name'];
                        if (isset($plan['ratesPerCopay'])) {
                            foreach ($plan['ratesPerCopay'] as $ratePerCopay) {
                                if ($ratePerCopay['healthPlanCoPaymentId'] == $data->health_plan_co_payment_id) {
                                    $response['priceWithVAT'] = (float) $ratePerCopay['discountPremium'] + (float) $ratePerCopay['vat'] + ((float) ($ratePerCopay['loadingPrice'] ?? 0));
                                    $response['priceWithLP'] = (float) $ratePerCopay['discountPremium'] + ((float) ($ratePerCopay['loadingPrice'] ?? 0));
                                }
                            }
                        }
                        $response['priceWithVAT'] = ((float) $response['priceWithVAT'] ?? 0) + ((isset($plan['basmah']) ? (float) $plan['basmah'] : 0)) + ((isset($plan['policyFee']) ? (float) $plan['policyFee'] : 0));
                        if (isset($plan['benefits'], $plan['benefits']['feature'])) {
                            foreach ($plan['benefits']['feature'] as $value) {
                                if ($value['code'] == GenericRequestEnum::TPA_Code) {
                                    $response['network'] = $value['value'];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $response;
    }

    public function healthPlanModify($request)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-health-quote-plans';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');
        $apiUserName = config('constants.KEN_API_USER');
        $apiPassword = config('constants.KEN_API_PWD');
        if ($request->planId && ! empty($request->planDetails)) {
            $membersBreakDown = [];
            $plansArray = [
                'planId' => (int) $request->planId,
                'isManualUpdate' => true,
                'memberPremiumBreakdown' => '',
            ];
            foreach ($request->planDetails as $value) {
                $array = [
                    'memberId' => (int) $value['memberId'],
                    'dob' => $value['dob'],
                    'gender' => $value['gender'],
                    'memberCategoryText' => $value['memberCategoryText'],
                ];
                if (isset($value['premium'])) {
                    $array['premium'] = (float) $value['premium'];
                }
                if (isset($value['basmah'])) {
                    $array['basmah'] = (int) $value['basmah'];
                }
                if (isset($value['vat'])) {
                    $array['vat'] = (int) $value['vat'];
                }

                array_push($membersBreakDown, $array);
            }
            $plansArray['memberPremiumBreakdown'] = $membersBreakDown;
            $dataArray = [
                'quoteUID' => $request->quoteUID,
                'update' => true,
                'plans' => [$plansArray],
            ];
            $apiCreds = [
                'apiEndPoint' => $apiEndPoint,
                'apiToken' => $apiToken,
                'apiTimeout' => $apiTimeout,
                'apiUserName' => $apiUserName,
                'apiPassword' => $apiPassword,
            ];
            $response = $this->httpService->processRequest($dataArray, $apiCreds);

            return $response;
        }
    }

    /**
     * Health Plan Edit V2. New method to handle the new health plan edit.
     */
    public function healthPlanModifyV2($request)
    {
        $loadingPrices = $request->get('loadingPrice');
        $manualPremiumPrices = $request->get('manualPremiumPrice');

        if (empty($request->get('selectedCopay'))) {
            $copayId = $request->get('defaultCopayId');
        } else {
            $selectedCopay = $request->get('selectedCopay');
            $copayId = $selectedCopay['id'];
        }

        if ($request->planId && ! empty($request->planDetails)) {
            $membersBreakDown = [];
            $plansArray = [
                'planId' => (int) $request->planId,
                'isManualUpdate' => (bool) $request->tagAsManual,
                'selectedCopayId' => (int) $copayId,
                'memberPremiumBreakdown' => '',
            ];
            foreach ($request->planDetails as $key => $value) {
                $toBeUpdatedCopay = [];
                if (isset($value['ratesPerCopay'])) {
                    foreach ($value['ratesPerCopay'] as $copay) {
                        if ((int) $copay['healthPlanCoPaymentId'] == (int) $copayId) {
                            if (
                                isset($loadingPrices[$key]) &&
                                (int) $loadingPrices[$key]['memberId'] == $value['memberId']
                            ) {
                                $copay['loadingPrice'] = (float) $loadingPrices[$key]['price'];
                            }
                            if (
                                isset($manualPremiumPrices[$key]) &&
                                (int) $manualPremiumPrices[$key]['memberId'] == $value['memberId']
                                && $manualPremiumPrices[$key]['premium'] != 0
                            ) {
                                $copay['basePrice'] = (float) $manualPremiumPrices[$key]['premium'];
                            }

                            array_push($toBeUpdatedCopay, $copay);
                        }
                    }
                }

                $array = [
                    'memberId' => (int) $value['memberId'],
                    'ratesPerCopay' => $toBeUpdatedCopay,
                ];
                array_push($membersBreakDown, $array);
            }
            $plansArray['memberPremiumBreakdown'] = $membersBreakDown;
            $dataArray = [
                'quoteUID' => $request->quoteUID,
                'update' => true,
                'plans' => [$plansArray],
                'callSource' => strtolower(LeadSourceEnum::IMCRM),
            ];

            info('Health Plan Modify V2 Request Data: '.json_encode($dataArray));
            $response = Ken::request('/save-manual-health-quote-plans', 'POST', $dataArray);

            return $response;
        }
    }

    public function healthQuoteAddMember($request)
    {
        $quoteId = $request->quoteId;

        if ($quoteId) {
            $memberDetails = [
                'firstName' => $request->first_name,
                'lastName' => $request->last_name ?? null,
                'emirateOfYourVisaId' => $request->emirate_of_your_visa_id,
                'gender' => $request->gender,
                'nationalityId' => $request->nationality_id,
                'memberCategoryId' => $request->member_category_id,
                'salaryBandId' => $request->salary_band_id,
                'dob' => Carbon::parse($request->dob)->toDateString(),
                'relationCode' => $request->relation_code,
            ];

            $dataArray = [
                'quoteUID' => $quoteId,
                'memberDetails' => [$memberDetails],
            ];

            $response = Ken::request('/add-health-quote-members', 'POST', $dataArray);
        } else {
            $response = [
                'status' => false,
                'message' => 'Quote Id not found',
            ];
        }

        return $response;
    }

    public function healthQuoteUpdateMember($request)
    {
        $quoteId = $request->quoteId ?? null;
        $memberId = $request->id ?? null;

        if ($quoteId && $memberId) {
            $memberDetails = [
                'id' => $memberId,
                'firstName' => $request->first_name,
                'lastName' => $request->last_name ?? null,
                'emirateOfYourVisaId' => $request->emirate_of_your_visa_id,
                'gender' => $request->gender,
                'nationalityId' => $request->nationality_id,
                'memberCategoryId' => $request->member_category_id,
                'salaryBandId' => $request->salary_band_id,
                'dob' => Carbon::parse($request->dob)->toDateString(),
                'relationCode' => $request->relation_code,
            ];

            $dataArray = [
                'quoteUID' => $quoteId,
                'memberDetails' => [$memberDetails],
            ];

            $response = Ken::request('/update-health-quote-members', 'POST', $dataArray);
        } else {
            $response = [
                'status' => false,
                'message' => 'Quote Id not found',
            ];
        }

        return $response;
    }

    public function healthQuoteDeleteMember($request)
    {
        $quoteId = $request->quoteId ?? null;
        $memberId = $request->customer_member_id ?? null;

        if ($quoteId && $memberId) {
            $memberDetails = [
                'id' => $memberId,
            ];

            $dataArray = [
                'quoteUID' => $quoteId,
                'memberDetails' => [$memberDetails],
            ];

            $response = Ken::request('/delete-health-quote-members', 'POST', $dataArray);
        } else {
            $response = [
                'status' => false,
                'message' => 'Member not found',
            ];
        }

        return $response;
    }

    /**
     * create health plan for upload & create process.
     *
     * @param  $data
     * @return false
     */
    public function renewalCreatePlan($planData)
    {
        $apiCreds = [
            'apiEndPoint' => config('constants.KEN2_API_ENDPOINT').'/save-manual-health-quote-plans',
            'apiToken' => config('constants.KEN_API_TOKEN'),
            'apiTimeout' => config('constants.KEN_API_TIMEOUT'),
            'apiUserName' => config('constants.KEN_API_USER'),
            'apiPassword' => config('constants.KEN_API_PWD'),
        ];

        $response = $this->httpService->processRequest($planData, $apiCreds);

        return $response;
    }

    /**
     * generate PDF for car quote plan and return.
     *
     * @return array|string[]
     */
    public function exportPlansPdf($quoteType, $data)
    {
        $planIds = $data['plan_ids'];
        $addons = (isset($data['addons'])) ? $data['addons'] : null;

        $quotePlans = $this->getQuotePlans($data['quote_uuid']);

        if (! isset($quotePlans->quote->plans)) {
            return ['error' => 'Quote plans not available'];
        }

        $providerIds = collect($quotePlans->quote->plans)->pluck('providerId')->toArray();
        $providers = InsuranceProvider::whereIn('id', $providerIds)->get()->keyBy('id')->toArray();

        $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);
        if (! $quote) {
            return ['error' => 'Quote Detail not available'];
        }
        $quote->load(['advisor' => function ($q) {
            $q->select('id', 'email', 'mobile_no', 'name', 'landline_no', 'profile_photo_path');
        }, 'customer']);

        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])
            ->loadView('pdf.health_quote_plans', compact('quotePlans', 'planIds', 'quote', 'addons', 'providers'));

        // generate pdf with file name e.g. InsuranceMarket.ae™ Motor Insurance Comparison for Rahul.pdf
        $pdfName = 'InsuranceMarket.ae™ Health Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';

        return ['pdf' => $pdf, 'name' => $pdfName];
    }

    public function statusesToDisplay($leadStatuses, $lead)
    {
        $statusesToRemove = collect();
        if ($lead->is_ecommerce) {
            if ($lead->quote_status_id != QuoteStatusEnum::QualificationPending) {
                $statusesToRemove->push(QuoteStatusEnum::QualificationPending);
            }
            if ($lead->quote_status_id != QuoteStatusEnum::Qualified) {
                $statusesToRemove->push(QuoteStatusEnum::Qualified);
            }
        }
        if (auth()->user()->hasRole(RolesEnum::HealthAdvisor)) {
            $lead->quote_status_id != QuoteStatusEnum::Fake && $statusesToRemove->push(QuoteStatusEnum::Fake);
            $lead->quote_status_id != QuoteStatusEnum::Duplicate && $statusesToRemove->push(QuoteStatusEnum::Duplicate);

            $lead->quote_status_id != QuoteStatusEnum::AMLScreeningCleared && $statusesToRemove->push(QuoteStatusEnum::AMLScreeningCleared);
            $lead->quote_status_id != QuoteStatusEnum::AMLScreeningFailed && $statusesToRemove->push(QuoteStatusEnum::AMLScreeningFailed);
        }

        return $leadStatuses->whereNotIn('id', $statusesToRemove);
    }

    public function getNonQuotedHealthPlans($insuranceProviderId, $quotePlanId, $networkId = null)
    {
        return HealthPlan::select('id', 'text')
            ->where('provider_id', $insuranceProviderId)
            ->where('health_rating_eligibility_id', $networkId)
            ->whereNotIn('id', $quotePlanId)
            ->where('is_active', true)
            ->get();
    }

    public function updateManualPlansBulk($request)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-health-quote-plans';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');
        $apiUserName = config('constants.KEN_API_USER');
        $apiPassword = config('constants.KEN_API_PWD');

        if ($request->planIds) {
            $data = $request->planIds;
            $isDisabled = $request->toggle;
            $plansArray = [];
            for ($i = 0; $i < count($data); $i++) {
                $apiArray = [
                    'planId' => (int) $data[$i],
                    'isHidden' => filter_var($isDisabled, FILTER_VALIDATE_BOOLEAN),
                    'isManualUpdate' => false,
                ];
                array_push($plansArray, $apiArray);
            }

            $dataArray = [
                'quoteUID' => $request->quote_uuid,
                'update' => true,
                'plans' => $plansArray,
            ];
            $apiCreds = [
                'apiEndPoint' => $apiEndPoint,
                'apiToken' => $apiToken,
                'apiTimeout' => $apiTimeout,
                'apiUserName' => $apiUserName,
                'apiPassword' => $apiPassword,
            ];

            $response = $this->httpService->processRequest($dataArray, $apiCreds);

            return $response;
        }
    }

    public function cancelPayment($request)
    {
        $embeddedProductOptionsIds = EmbeddedProductOption::where('embedded_product_id', $request->embedded_id)->pluck('id');
        $type = QuoteType::where('code', $request->modelType)->first();
        $embededTransaction = EmbeddedTransaction::where('quote_request_id', $request->quote_id)
            ->where('quote_type_id', $type->id)
            ->whereIn('product_id', $embeddedProductOptionsIds)
            ->first();
        if (isset($embededTransaction->payments[0])) {
            $payment = $embededTransaction->payments[0];
            $maxAmount = $payment->premium_captured - $payment->premium_refunded;
            if ($maxAmount >= $request->amount) {
                $paymentAction = new PaymentAction;
                $paymentAction->payment_code = $payment->code; // $embededTransaction->code;
                $paymentAction->is_fulfilled = 0;
                $paymentAction->action_type = 'REFUND';
                $paymentAction->reason = $request->reason;
                $paymentAction->amount = $request->amount;
                $paymentAction->created_by = auth()->user()->email;

                $paymentAction->save();
                $data = [
                    'uuid' => $request->uuid,
                    'type_id' => $type->id,
                    'code' => $payment->code,

                ];
                $processResponse = $this->processCancelPayment($data);

                return response($processResponse, 403);
            } else {
                return response(['should not be maximum'], 403);
            }
        }

        return response(['Payment not exist'], 403);
    }

    public function toggleSelection($data, $quoteTypeId)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/toggle-embedded-product';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');
        $apiUserName = config('constants.KEN_API_USER');
        $apiPassword = config('constants.KEN_API_PWD');

        $toggleData = [
            'quoteUid' => $data->quote_uuid,
            'quoteTypeId' => $quoteTypeId,
            'epOptionId' => $data->id,
        ];

        $apiCreds = [
            'apiEndPoint' => $apiEndPoint,
            'apiToken' => $apiToken,
            'apiTimeout' => $apiTimeout,
            'apiUserName' => $apiUserName,
            'apiPassword' => $apiPassword,
        ];

        $response = $this->httpService->processRequest($toggleData, $apiCreds);

        return $response;
    }

    public function processCancelPayment($data)
    {
        $apiEndPoint = config('constants.MARSHALL_API_ENDPOINT').'/payment/checkout/cancel';
        $apiToken = config('constants.MARSHALL_API_TOKEN');
        $apiTimeout = config('constants.MARSHALL_API_TIMEOUT');
        $apiUserName = config('constants.MARSHALL_API_USER');
        $apiPassword = config('constants.MARSHALL_API_PWD');

        $carPlanData = [
            'quoteUID' => $data['uuid'],
            'quoteTypeId' => $data['type_id'],
            'payments' => [
                [
                    'codeRef' => $data['code'],
                ],
            ],
        ];

        $apiCreds = [
            'apiEndPoint' => $apiEndPoint,
            'apiToken' => $apiToken,
            'apiTimeout' => $apiTimeout,
            'apiUserName' => $apiUserName,
            'apiPassword' => $apiPassword,
        ];

        $response = $this->httpService->processRequest($carPlanData, $apiCreds);

        return $response;
    }

    public function assignLeadDirectlyForQA(int $userId, $lead): void
    {
        info('inside the check for manual assignment QA');
        $lead->advisor_id = $userId;
        $lead->save();

        if ($lead->quote_status_id == QuoteStatusEnum::Qualified) {
            IntroEmailJob::dispatch(quoteTypeCode::Health, 'Capi', $lead->uuid, 'send-rm-intro-email', null, false)->delay(now()->addSeconds(3));
        }
    }

    public function validateLead($lead, mixed $leadId, array $result, bool $skipLead, int $userId): array
    {
        if ($lead->health_team_type == null || $lead->health_team_type == '') {
            $msg = 'Health team is missing please select health team first';
            array_push($result, ['leadId' => $lead->code, 'msg' => $msg]);
            $skipLead = true;
        }

        $user = User::where('id', $userId)->first();
        $subTeam = Team::where('id', $user->sub_team_id)->first();
        if (strtolower($subTeam->name) != strtolower($lead->health_team_type)) {
            $msg = 'User sub team mismatch with lead health team';
            array_push($result, ['leadId' => $lead->code, 'msg' => $msg]);
            $skipLead = true;
        }

        return [$result, $skipLead];
    }

    public function assignRenewalBatch($id)
    {
        $date = Carbon::today()->toDateString();

        $renewalBatch = RenewalBatch::select('name')->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($renewalBatch) {
            $healthQuote = HealthQuote::find($id);
            if ($healthQuote) {
                $healthQuote->update(['renewal_batch' => $renewalBatch->name]);
            }
        }
    }

    public function getCopaysByPlanId($planId)
    {
        $copays = DB::table('health_plan_co_payments')
            ->select('id', 'text')
            ->where('health_plan_id', $planId)
            ->get()->toArray();

        return $copays;
    }

    public function updateNotifyAgentFlag($request)
    {
        if (empty($request->get('selectedCopay'))) {
            $copayId = $request->get('defaultCopayId');
        } else {
            $selectedCopay = $request->get('selectedCopay');
            $copayId = $selectedCopay['id'];
        }

        $dataArray = [
            'quoteUID' => $request->quoteUID,
            'planId' => $request->get('planId'),
            'memberId' => $request->get('memberId'),
            'healthPlanCoPaymentId' => $copayId,
            'notifyAgent' => $request->get('notifyAgent'),
        ];

        $response = Ken::request('/update-notify-agent', 'POST', $dataArray);

        return $response;
    }

    public function exportRmLeads()
    {
        $request = request();
        [$startDate, $endDate] = $this->getTransactionApprovedDates($request);

        $carTeam = $this->getProductByName(quoteTypeCode::Car);
        $healthTeam = $this->getProductByName(quoteTypeCode::Health);

        return DB::table('health_quote_request as q')
            ->select([
                'q.code as Ref_Id',
                DB::raw("DATE_FORMAT(q.transaction_approved_at, '%m/%d/%Y') as Transaction_Approved_At"),
                'u.name as Advisor_Name',
                'u.email as Advisor_Email',
                'qs.text as Lead_Status',
                'ps.text as Payment_Status',
                DB::raw("DATE_FORMAT(q.created_at, '%m/%d/%Y') as Created_At"),
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT t1.name SEPARATOR ', ')
                      FROM teams t1
                      WHERE t1.parent_team_id = $carTeam->id
                      AND t1.name IN (
                          SELECT t2.name
                          FROM teams t2
                          JOIN user_team ut2 ON t2.id = ut2.team_id
                          WHERE ut2.user_id = u.id)
                      ) AS CarTeams"),
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT t1.name SEPARATOR ', ')
                      FROM teams t1
                      WHERE t1.parent_team_id = $healthTeam->id
                      AND t1.name IN (
                          SELECT t2.name
                          FROM teams t2
                          JOIN user_team ut2 ON t2.id = ut2.team_id
                          WHERE ut2.user_id = u.id)
                      ) AS HealthTeams"),
                DB::raw("GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') AS AdvisorTeamName"),
            ])
            ->leftJoin('quote_status as qs', 'q.quote_status_id', '=', 'qs.id')
            ->leftJoin('payment_status as ps', 'q.payment_status_id', '=', 'ps.id')
            ->leftJoin('users as u', 'q.advisor_id', '=', 'u.id')
            ->leftJoin('user_team as ut', 'q.advisor_id', '=', 'ut.user_id')
            ->leftJoin('teams as t', 'ut.team_id', '=', 't.id')
            ->whereBetween('q.transaction_approved_at', [$startDate, $endDate])
            ->whereIn('u.id', function ($subQuery) use ($carTeam) {
                $subQuery->select('u.id')
                    ->from('users as u')
                    ->leftJoin('user_team as ut', 'u.id', '=', 'ut.user_id')
                    ->leftJoin('teams as t', 'ut.team_id', '=', 't.id')
                    ->where('t.parent_team_id', $carTeam->id);
            })
            ->groupBy('q.code', 'q.transaction_approved_at', 'u.name', 'u.email', 'qs.text', 'ps.text', 'q.created_at')
            ->orderBy('q.created_at', 'ASC');
    }

    private function getTransactionApprovedDates($request)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH'); // Default format
        if (isset($request->transaction_approved_dates)) {
            $dates = $request->transaction_approved_dates;

            // If the dates are a string, split it into an array
            if (is_string($dates)) {
                $dates = explode(',', $dates);
            }

            // Parse start and end dates if the array is valid
            if (is_array($dates) && count($dates) === 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay()->format($dateFormat);
                $endDate = Carbon::parse($dates[1])->endOfDay()->format($dateFormat);

                return [$startDate, $endDate];
            }
        }

        // Default to the current month's start and end of the previous day
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');
        $endOfPreviousDay = Carbon::now()->subDay()->format('Y-m-d 23:59:59');

        return [$startOfMonth, $endOfPreviousDay];
    }
}
