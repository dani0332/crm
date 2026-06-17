<?php

namespace App\Services;

use App\Builders\HealthQuoteQueryBuilder;
use App\Enums\AMLStatusCode;
use App\Enums\AssignmentTypeEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthTeamType;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LeadSourceTypes;
use App\Enums\LookupsEnum;
use App\Enums\PaymentGatewayEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SLAActionTypeEnum;
use App\Facades\Ken;
use App\Jobs\GetQuotePlansJob;
use App\Jobs\IntroEmailJob;
use App\Jobs\ReEvaluatePecJob;
use App\Jobs\SendSupportUserAssignmentEmailJob;
use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\Customer;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Models\HealthMemberDetail;
use App\Models\HealthPlan;
use App\Models\HealthQuote;
use App\Models\HealthQuotePlan;
use App\Models\HealthQuoteRequestDetail;
use App\Models\InsuranceProvider;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\PaymentAction;
use App\Models\QuoteBatches;
use App\Models\QuoteType;
use App\Models\RenewalBatch;
use App\Models\Team;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\SLA\SLAService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\GetUserTreeTrait;
use App\Traits\HealthServiceUtils;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use GuzzleHttp\Exception\BadResponseException;
use Hidehalo\Nanoid\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use PDF;

class HealthQuoteService extends BaseService
{
    protected $query;
    protected $leadAllocationService;
    protected $httpService;

    const SELECT_TITLE_MULTIPLE = 'select|title|multiple';
    const APPLICATION_JSON = 'application/json';

    use AddPremiumAllLobs, GenericQueriesAllLobs, GetUserTreeTrait, HealthServiceUtils, RolePermissionConditions;

    public function __construct(HttpRequestService $httpService, LeadAllocationService $leadAllocationService, protected HealthQuoteQueryBuilder $healthQuoteQueryBuilder, protected SLAService $slaService)
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
            'hqr.notional_team',
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
            'su.name as support_user_name',
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
            DB::raw('DATE_FORMAT(hqr.previous_policy_start_date, "%d-%m-%Y") as previous_policy_start_date'),
            'hqr.previous_quote_policy_premium',
            'hqr.renewal_upload_plan_code',
            'hqr.renewal_upload_copay_code',
            'hqr.renewal_upload_payment_link',
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
            DB::raw('COALESCE(insured.customer_type, "'.CustomerTypeEnum::Individual.'") as customer_type'),
            'insured.first_name as insured_first_name',
            'insured.last_name as insured_last_name',
            'insured_kyc.id as insured_kyc_id',
            DB::raw('IF(insured.id_type = "emiratesId", insured.id_number, "") as emirates_id_number'),
            'insured.id_type as insured_id_type',
            'insured.id_number as insured_id_number',
            'insured_kyc.id_expiry_date as emirates_id_expiry_date',
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
            'hqr.digital_signatory',
            'hqr.uae_pass_api_status',
            DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
            DB::raw('DATE_FORMAT(hqr.transaction_approved_at, "%d-%m-%Y %H:%i:%s") as transaction_approved_at'),
            'hqr.insly_migrated',
            'hqr.sic_advisor_requested',
            'hqr.aml_status',
            'hqr.insurance_provider_id',
            DB::raw('
                CASE
                    WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningPending.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningPending).'"
                    WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningCleared.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningCleared).'"
                    WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningFailed.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningFailed).'"
                    WHEN insurer_aml_status IS NULL THEN "'.AMLStatusCode::InsurerAMLScreeningNA.'"
                    ELSE insurer_aml_status
                END AS insurer_aml_status_display
            '),
            'c.pcp_tag',
            'hqr.pc_qualified',
            DB::raw(Customer::formattedPcpTagCase().' as pcp_tag_formatted'),
            DB::raw(HealthQuote::formattedPcQualifiedCase().' as pc_qualified_formatted'),
            // Sub-source fields
            'hqr.sub_source_id',
            'hqr.sub_source_options_id',
            'ss.text as sub_source_text',
            'ss.description as sub_source_description',
            'sso.text as sub_source_option_text',
            'sso.description as sub_source_option_description',
            'ub.branch_id as advisor_primary_branch_id',
            'b.name as lead_branch_name',
            'b.id as lead_branch_id',
            'is_quote_locked',
            'is_branch_applicable',
            'hqr.api_issuance_status_id',
            'hqr.insurer_api_status_id',
            'hqr.policy_holder_category_code',
            'hqr.visa_category_id',
            'hqr.insure_code',
            'hqr.policy_holder_code',
        )
            ->leftJoin('payments as py', 'py.code', '=', 'hqr.code')
            ->leftJoin('marital_status as ms', 'ms.id', '=', 'hqr.marital_status_id')
            ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'hqrd.lost_reason_id')
            ->leftJoin('health_cover_for as hcf', 'hcf.id', '=', 'hqr.cover_for_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'hqr.nationality_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'hqr.transaction_type_id')
            ->leftJoin('lookups as ss', 'ss.id', '=', 'hqr.sub_source_id')
            ->leftJoin('lookups as sso', 'sso.id', '=', 'hqr.sub_source_options_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'hqr.emirate_of_your_visa_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('health_lead_type as lt', 'lt.id', '=', 'hqr.lead_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
            ->leftJoin('users as su', 'su.id', '=', 'hqr.support_user_id')
            ->leftJoin('users as uadv', 'uadv.id', '=', 'hqr.previous_advisor_id')
            ->leftJoin('users as wcu', 'wcu.id', '=', 'hqr.wcu_id')
            ->leftJoin('salary_band as sb', 'sb.id', '=', 'hqr.salary_band_id')
            ->leftJoin('health_plan as hp', 'hp.id', '=', 'hqr.plan_id')
            ->leftJoin('insurance_provider as ihp', 'ihp.id', '=', 'hp.provider_id')
            ->leftJoin('member_category as mc', 'mc.id', '=', 'hqr.member_category_id')
            ->leftJoin('insurance_provider as ins_provider', 'ins_provider.id', '=', 'hqr.currently_insured_with_id')
            ->leftjoin('payment_status', 'py.payment_status_id', 'payment_status.id')
            ->leftJoin('customer as c', 'hqr.customer_id', 'c.id')
            ->leftJoin('renewal_batches as rb', 'hqr.renewal_batch_id', '=', 'rb.id')
            ->leftJoin('quote_request_entity_mapping as qrem', function ($entityMappingJoin) {
                $entityMappingJoin->on('qrem.quote_type_id', '=', DB::raw(QuoteTypeId::Health));
                $entityMappingJoin->on('qrem.quote_request_id', '=', 'hqr.id');
            })
            ->leftJoin('customer_insured as ic', function ($insuredCustomerMapping) {
                $insuredCustomerMapping->on('ic.quote_type_id', '=', DB::raw(QuoteTypeId::Health));
                $insuredCustomerMapping->on('ic.quote_request_id', '=', 'hqr.id');
                $insuredCustomerMapping->where('ic.is_active', '=', true);
            })
            ->leftJoin('insured', 'ic.insured_id', '=', 'insured.id')
            ->leftJoin('entities as ent', 'qrem.entity_id', '=', 'ent.id')
            ->leftJoin('insured_kyc', 'insured.id', '=', 'insured_kyc.insured_id')
            ->leftJoin('user_branches as ub', function ($join) {
                $join->on('ub.user_id', '=', 'hqr.advisor_id')
                    ->where('ub.is_primary', '=', 1)
                    ->where('ub.status', '=', 1);
            })
            ->leftJoin('branches as b', 'b.id', '=', 'hqr.branch_id');
    }

    public function getEntity($id)
    {
        return $this->query->addSelect(['hqr.email', 'hqr.mobile_no', 'hqr.pq_advisor_id'])->where('hqr.uuid', $id)->first();
    }

    public function getLead($id): HealthQuote
    {
        return HealthQuote::findOrFail($id);
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
                            'paymentCharges',
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
        $subSourceId = $request->sub_source_id ?? null;
        $subSource = Lookup::find($subSourceId);
        $sendOcbEmail = true;
        if ($subSource && $subSource?->code == 'strategic-partners-referrals') {
            $sendOcbEmail = false;
        }
        $mobileNo = ($request->mobile_dial_code ?? '').($request->mobile_national_no ?? '');

        $dataArr = [
            'callSource' => strtolower(LeadSourceEnum::IMCRM),
            'email' => $request->email,
            'details' => $request->details,
            'mobileNo' => $mobileNo,
            'preference' => $request->preference,
            'source' => $sourceName,
            'maritalStatusId' => $request->marital_status_id,
            'premium' => $request->premium,
            'leadTypeId' => $request->lead_type_id,
            'referenceUrl' => config('constants.APP_URL'),
            'isEbpRenewal' => $request->is_ebp_renewal == 'on' ? true : false,
            'coverForId' => $request->cover_for_id,
            'hasDental' => $request->has_dental == 'on' ? true : false,
            'hasWorldwideCover' => $request->has_worldwide_cover == 'on' ? true : false,
            'hasHome' => $request->has_home == 'on' ? true : false,
            'currentlyInsuredWithId' => $request->currently_insured_with_id,
            'healthPlanTypeId' => $request->plan_type_id,
            // Sub-source fields from CreateLeadModal
            'subSourceId' => $request->sub_source_id ?? null,
            'subSourceOptionsId' => $request->sub_source_options_id ?? null,
            'additionalNotes' => $request->additional_notes ?? null,
            'insureCode' => $request->health_insure_code, // WHO WOULD THE CUSTOMER LIKE TO INSURE?
            'policyHolderCode' => $request->policy_holder_code, // WHO WILL BE THE POLICYHOLDER?
            'policyNumber' => $request->policy_number,
            'policyHolderCategoryCode' => $request->policy_holder_category_code,
            'policyStartDate' => $request->policy_start_date,
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'sendOcbEmail' => $sendOcbEmail,
            'userId' => auth()->user()->id,
            'customerType' => CustomerTypeEnum::Individual,
        ];

        // Log lead source parameters for Health quotes
        info('Health saveHealthQuote - Lead source parameters:', [
            'type' => $request->input('type'),
            'subSourceId' => $request->sub_source_id,
            'subSourceOptionsId' => $request->sub_source_options_id,
            'additionalNotes' => $request->additional_notes,
        ]);

        $dataArr['memberDetails'] = collect($request->members)->map(fn ($member) => $this->prepareMemberDetailPayload($member))->all();

        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;

            if (Auth::user()->hasAnyRole([RolesEnum::CLIENTSUPPORTLEAD, RolesEnum::CLIENTSUPPORT])) {
                $dataArr['supportUserId'] = Auth::user()->id;
            }
        }

        LoggerService::info('Health saveHealthQuote - CAPI API request', extra: ['request' => $dataArr]);

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-health-quote', $dataArr, HealthQuote::class);

        LoggerService::info('Health saveHealthQuote - CAPI API response', extra: ['response' => $response]);

        if (isset($response->quoteUID)) {

            LoggerService::info('Health saveHealthQuote - after CAPI request - advisorId and supportUserId:', [
                'advisorId' => $dataArr['advisorId'] ?? null,
                'supportUserId' => $dataArr['supportUserId'] ?? null,
                'quoteUID' => $response->quoteUID ?? null,
            ]);

            $this->savePremium(quoteTypeCode::HealthQuote, $request, $response);
            $subTeam = null;
            if (auth()->user()->subTeam) {
                $subTeam = auth()->user()->subTeam->name;
            }
            HealthQuote::where('uuid', $response->quoteUID)->update(['health_team_type' => $subTeam]);

            $this->selfAssign(QuoteTypes::HEALTH, $response->quoteUID);
        }

        return $response;
    }

    public function getGridData($model = null, $requestParams = [])
    {
        $query = $this->healthQuoteQueryBuilder->processGridData($requestParams);

        if (Auth::check() && Auth::user()->hasRole(RolesEnum::PreQualificationAdvisor)) {
            $query->where('health_quote_request.pq_advisor_id', Auth::id());
            $query->whereNull('health_quote_request.health_plan_type');
        } elseif (Auth::check() && Auth::user()->hasRole(RolesEnum::PreQualificationLead)) {

        } else {
            $this->whereBasedOnRole($query, 'health_quote_request', quoteTypeCode::Health, user: $requestParams['user'] ?? null);
        }

        $this->adjustQueryByDateFilters($query, 'health_quote_request', $requestParams);

        return $query;
    }

    public function postProcessHealthQuotes($quotes)
    {
        return $quotes->map(function ($quote) {
            $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Health, $quote->emirate_of_your_visa_id));
            $quote->is_entity = $quote->isEntity();
            $quote->is_migrated = $quote->isMigrated();
            $quote->is_policyholder_included = $quote->isPolicyholderIncluded();

            return $quote;
        });
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
        if ($healthQuote?->is_quote_locked) {
            return redirect('quote/health/'.$id)->with('error', 'Edits are not permitted once the lead has reached Transaction Approved status');
        }

        $customer = $healthQuote?->customer;
        $isEntity = $request->customer_type == CustomerTypeEnum::Entity;
        $sourceName = $request->is_ebp_renewal == 'on' ? LeadSourceTypes::EBPRENEWALS : $healthQuote->source;
        $priceStartingFrom = $healthQuote?->price_starting_from ?? null;
        $members = collect($request->members);
        $principalMember = $members->firstWhere('is_principal', 1);
        $mobileNo = ($request->mobile_dial_code ?? '').($request->mobile_national_no ?? '');
        $emirateOfVisaId = $isEntity ? $healthQuote->emirate_of_your_visa_id : ($principalMember['emirate_of_your_visa_id'] ?? null);
        $nationalityId = $isEntity ? $healthQuote->nationality_id : ($principalMember['nationality_id'] ?? null);
        $gender = $isEntity ? $healthQuote->gender : ($principalMember['gender'] ?? null);
        $dob = $isEntity ? $healthQuote->dob : ($principalMember['dob'] ?? null);
        $salaryBandId = $isEntity ? $healthQuote->salary_band_id : ($principalMember['salary_band_id'] ?? null);
        $memberCategoryId = $isEntity ? $healthQuote->member_category_id : ($principalMember['member_category_id'] ?? null);
        $dataArr = [
            'callSource' => strtolower(LeadSourceEnum::IMCRM),
            'quoteUID' => $id,
            'userId' => auth()->user()->id,
            'data' => [
                'receiveMarketingUpdates' => $customer?->receive_marketing_updates,
                'email' => $request->email,
                'details' => $request->details,
                'mobileNo' => $mobileNo,
                'preference' => $request->preference,
                'source' => $sourceName,
                'leadTypeId' => $request->lead_type_id,
                'isEbpRenewal' => $request->is_ebp_renewal == 'on' ? true : false,
                'hasDental' => $request->has_dental == 'on' ? true : false,
                'hasWorldwideCover' => $request->has_worldwide_cover == 'on' ? true : false,
                'hasHome' => $request->has_home == 'on' ? true : false,
                'healthPlanTypeId' => $request->plan_type_id,
                'additionalNotes' => $request->additional_notes ?? null,
                'insureCode' => $request->health_insure_code,
                'policyHolderCode' => $request->policy_holder_code,
                'policyNumber' => $request->policy_number,
                'policyHolderCategoryCode' => $request->policy_holder_category_code,
                'policyStartDate' => $request->policy_start_date,
                'firstName' => $request->first_name,
                'lastName' => $request->last_name,
                'coverForId' => $request->cover_for_id,

                // principal member details
                'memberCategoryId' => $memberCategoryId,
                'emirateOfYourVisaId' => $emirateOfVisaId,
                'nationalityId' => $nationalityId,
                'gender' => $gender,
                'dob' => $dob,
                'salaryBandId' => $salaryBandId,

                // confirm with Waleeb about this param
                'priceStartingFrom' => $priceStartingFrom,
                'customerType' => $request->customer_type ?? null,
            ],
        ];

        if ($isEntity) {
            $dataArr['data']['visaCategoryId'] = $healthQuote->visa_category_id;
            $dataArr['data']['maritalStatusId'] = $healthQuote->marital_status_id;
        }

        if ($request->customer_type == CustomerTypeEnum::Individual) {
            $dataArr['data']['memberDetails'] = $members->map(fn ($member) => $this->prepareMemberDetailPayload($member))->all();
        }

        if ($request->has('sub_source_id')) {
            $dataArr['subSourceId'] = $request->sub_source_id ?? null;
        }

        if ($request->has('sub_source_options_id')) {
            $dataArr['subSourceOptionsId'] = $request->sub_source_options_id ?? null;
        }

        LoggerService::info('Health updateHealthQuote - CAPI API request', extra: ['request' => $dataArr, 'uuid' => $id]);

        $response = CapiRequestService::sendCAPIRequest('/api/v1-update-health-quote', $dataArr, HealthQuote::class);

        LoggerService::info('Health updateHealthQuote - CAPI API response', extra: ['response' => $response, 'uuid' => $id]);

        if (! isset($response?->data?->id)) {
            return false;
        }

        LoggerService::info('Health updateHealthQuote - after CAPI request - advisorId and supportUserId:', [
            'quoteUID' => $response?->data?->uuid ?? null,
        ]);

        $this->slaService->meetSLAOnEdit($healthQuote, SLAActionTypeEnum::LEAD_EDIT);

        ReEvaluatePecJob::dispatch($healthQuote->uuid);
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
            'quote_status_id' => self::SELECT_TITLE_MULTIPLE,
            'advisor_id' => self::SELECT_TITLE_MULTIPLE,
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
            'nationality_id' => 'select|title',
            'lead_type_id' => 'select|title',
            'has_dental' => 'input|checkbox|title',
            'has_worldwide_cover' => 'input|checkbox|title',
            'has_home' => 'input|checkbox|title',
            'emirate_of_your_visa_id' => 'select|title',
            'previous_quote_id' => 'readonly|title',
            'policy_expiry_date' => 'input|date|title|range',
            'is_renewal' => '|static|'.GenericRequestEnum::Yes.','.GenericRequestEnum::No.'',
            'salary_band_id' => 'select|title',
            'member_category_id' => 'select|title',
            'gender' => 'select',
            'renewal_batches' => self::SELECT_TITLE_MULTIPLE,
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
            'payment_status_id' => 'select|title',
            LookupsEnum::HEALTH_INSURE_OPTIONS->value => 'select',
            LookupsEnum::POLICY_HOLDER_OPTIONS->value => 'select',
            LookupsEnum::POLICY_HOLDER_CATEGORY->value => 'select',
            'visa_category' => 'select',
            LookupsEnum::HEALTH_MEMBER_RELATION->value => 'select',
            LookupsEnum::DOMESTIC_WORKER_RELATION->value => 'select',
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
                        'Content-Type' => self::APPLICATION_JSON,
                        'Accept' => self::APPLICATION_JSON,
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
        } catch (BadResponseException $e) {
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
                        'Content-Type' => self::APPLICATION_JSON,
                        'Accept' => self::APPLICATION_JSON,
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
        } catch (BadResponseException $e) {
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
        $entity->assignment_type = null;
        $entity->quote_status_id = $quoteStatusId;
        $entity->save();
    }

    public function assignWCU($request): array
    {
        $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        LoggerService::info('Leads ids to assign: '.json_encode($leadsIds));
        $userId = $request->assigned_to_id_new;
        $result = [];
        foreach ($leadsIds as $leadId) {
            $lead = $this->getEntityPlain($leadId);
            if ($this->isLeadTransactionApproved($lead) && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
                LoggerService::warning('Cannot assign WCU as lead is in Transaction Approved state, lead id: '.$leadId);
                array_push($result, ['leadId' => $lead->code, 'msg' => 'Cannot assign WCU as lead is in Transaction Approved state']);

                continue;
            } elseif ($lead) {
                $lead->advisor_id = null;
                $lead->assignment_type = null;
                $lead->quote_status_id = QuoteStatusEnum::NewLead;
                $lead->wcu_id = $userId;
                $lead->health_team_type = $request->assign_team;
                $lead->save();
                LoggerService::info('WCU advisor: '.$userId.' assigned to lead: '.$leadId);
            }
        }

        return $result;
    }

    public function assignHealthTeam($request, $lead): bool
    {
        if ($this->isLeadTransactionApproved($lead) && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
            LoggerService::warning('Cannot assign Health Team as lead is in Transaction Approved state');

            return false;
        }
        if ($lead->health_team_type != null && $lead->advisor_id != null) {
            LoggerService::info('Removing previous advisor as lead already assigned to a health team');
            $this->removePreviousAdvisorAndUpdateStatus($lead, QuoteStatusEnum::Qualified);
        }
        $selectedTeam = $request->get('assign_team');
        if ($selectedTeam == quoteTypeCode::GM) {
            LoggerService::info("Assigning lead to GM Ref-ID: {$lead->uuid}");
            $this->convertLeadToGM($lead);
            $lead->health_team_type = quoteTypeCode::GM;
        } else {
            LoggerService::info("Assigning lead to {$selectedTeam} team");
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
        $jobs = [];
        $delayCounter = 0;

        foreach ($leadsIds as $leadId) {
            $currentJobChains = [];
            $lead = $this->getEntityPlain($leadId);

            if (isset($request->assign_team) && $request->assign_team !== '') {
                $lead->health_team_type = $request->assign_team;
            }

            $oldAssignmentType = $lead->assignment_type;

            $isReassignment = $lead->advisor_id != null ? true : false; // checking if the advisor is already assigned or not for reassignment email template

            $previousAdvisorId = $lead->advisor_id; // saving previous advisor before updating the new to update the counts

            $lead->advisor_id = $userId;

            $lead->assignment_type = $isReassignment ? AssignmentTypeEnum::MANUAL_REASSIGNED : AssignmentTypeEnum::MANUAL_ASSIGNED;

            LoggerService::info(self::class.' - processManualLeadAssignment: Checking lead_assignment_trigger', extra: [
                'current_value' => $lead->lead_assignment_trigger ?? 'null',
            ]);
            if (empty($lead->lead_assignment_trigger)) {
                LoggerService::info(self::class.' - processManualLeadAssignment: Setting lead_assignment_trigger to MANUAL_ALLOCATION');
                $lead->lead_assignment_trigger = LeadAssignmentTriggerEnum::MANUAL_ALLOCATION;
            }
            // will update the car quote request detail entity about assignment
            $oldAdvisorAssignedDate = $this->updateChildRecord($lead->id, $userId);

            LoggerService::info('Manual assignment done and details table updated for lead: '.$lead->uuid.' and old advisor assigned date is: '.$oldAdvisorAssignedDate.' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);
            // update new and previous (if applicable) advisor counts in lead allocation table
            $this->addManualAllocationCountAndUpdate($userId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $oldAssignmentType, $quote_type);
            // update existing record of quote view count if exists and reset count to zero
            $this->addOrUpdateQuoteViewCount($lead, QuoteTypeId::Health, $userId);

            $lead->quote_batch_id = $quoteBatch->id;

            $lead->save();

            $currentJobChains[] = new GetQuotePlansJob($lead);
            if (in_array($lead->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED])) {
                $currentJobChains[] = (new IntroEmailJob(quoteTypeCode::Health, 'Capi', $lead->uuid, 'send-rm-intro-email', $previousAdvisorId, $isReassignment))->delay(now()->addSeconds(15 + $delayCounter));
                $delayCounter += 15;
            }
            $jobs[] = $currentJobChains;
        }

        if ($jobs != null && count($jobs) > 0) {
            Bus::batch($jobs)
                ->name('Health Leads Manual Assignment')
                ->dispatch();
        }

        return [];
    }

    public function addManualAllocationCountAndUpdate($newAdvisorId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $quoteType = null)
    {
        // Check if $lead or $newAdvisorId is not provided
        if ($lead === null || $newAdvisorId === null) {
            LoggerService::error('Lead or new advisor ID is null, unable to update allocation counts');

            return;
        }

        // Skip allocation count updates for IMCRM source leads
        if ($lead->source === LeadSourceEnum::IMCRM) {
            LoggerService::info('Skipping allocation count update for IMCRM source lead: '.$lead->uuid);

            return;
        }

        LoggerService::info('Previous assignment type is: '.$previousAssignmentType);

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

        if (empty($data->plan_id)) {
            return $response;
        }

        $kenResponse = Ken::request('/fetch-health-selected-plan', 'post', [
            'quoteUID' => $data->uuid,
        ]);

        $plans = collect($kenResponse['plans'] ?? []);

        $plan = (object) $plans->first();

        if ($plan) {
            $response['providerName'] = property_exists($plan, 'providerName') ? $plan->providerName : '';
            $response['paymentStatus'] = GenericRequestEnum::NotApplicable;
            $response['paidAt'] = GenericRequestEnum::NotApplicable;
            $response['planName'] = property_exists($plan, 'name') ? $plan->name : '';

            if (property_exists($plan, 'ratesPerCopay')) {
                foreach ($plan->ratesPerCopay as $ratePerCopay) {
                    if (isset($ratePerCopay['healthPlanCoPaymentId']) && $ratePerCopay['healthPlanCoPaymentId'] == $data->health_plan_co_payment_id) {
                        $response['priceWithVAT'] = (float) $ratePerCopay['discountPremium'] + (float) $ratePerCopay['vat'] + ((float) ($ratePerCopay['loadingPrice'] ?? 0)) + ((float) ($ratePerCopay['adjustedPrice'] ?? 0));
                        $response['priceWithLP'] = (float) $ratePerCopay['discountPremium'] + ((float) ($ratePerCopay['loadingPrice'] ?? 0)) + ((float) ($ratePerCopay['adjustedPrice'] ?? 0));
                    }
                }
            }

            $basmah = property_exists($plan, 'basmah') ? (float) $plan->basmah : 0;
            $policyFee = property_exists($plan, 'policyFee') ? (float) $plan->policyFee : 0;
            $icpFee = property_exists($plan, 'icpFee') ? (float) $plan->icpFee : 0;

            $response['priceWithVAT'] = ((float) $response['priceWithVAT'] ?? 0) + $basmah + $policyFee + $icpFee;
            $benefits = property_exists($plan, 'benefits') ? $plan->benefits : [];
            if (isset($benefits['feature'])) {
                $features = $benefits['feature'];
                foreach ($features as $value) {
                    if (isset($value['code']) && $value['code'] == GenericRequestEnum::TPA_Code) {
                        $response['network'] = $value['value'];
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
        $adjustingPrices = $request->get('adjustingPrice');
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
                                isset($adjustingPrices[$key]) &&
                                (int) $adjustingPrices[$key]['memberId'] == $value['memberId']
                            ) {
                                $copay['adjustedPrice'] = (float) $adjustingPrices[$key]['price'];
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

            LoggerService::info('Health Plan Modify V2 Request Data: ', $dataArray);
            $response = Ken::request('/save-manual-health-quote-plans', 'POST', $dataArray);

            return $response;
        }
    }

    public function healthQuoteAddMember($request)
    {
        $quoteId = $request->quoteId;

        if ($quoteId) {
            $memberDetails = $this->prepareMemberDetailPayload($request->all());

            $dataArray = [
                'quoteUID' => $quoteId,
                'memberDetails' => [$memberDetails],
                'userId' => auth()->user()->id,
                'callSource' => strtolower(LeadSourceEnum::IMCRM),
            ];

            LoggerService::info('Health quote add member - Ken API request', extra: ['request' => $dataArray, 'uuid' => $quoteId]);

            $response = Ken::request('/add-health-quote-members', 'POST', $dataArray);

            LoggerService::info('Health quote add member - Ken API response', extra: ['response' => $response, 'uuid' => $quoteId]);

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

        $isInvalidMemberId = fn ($id) => empty($id) || str_starts_with((string) $id, 'temp-');

        $membersMissingId = ! empty($request->members)
            ? collect($request->members)->filter(fn ($m) => $isInvalidMemberId($m['id'] ?? null))->isNotEmpty()
            : $isInvalidMemberId($request->id);

        if (! $quoteId) {
            return ['status' => false, 'message' => 'Quote Id not found'];
        }

        // Bulk path (policyholder change): each member must carry its id.
        // Single path (regular edit): top-level id is required.
        if ($membersMissingId) {
            return ['status' => false, 'message' => 'Member Id not found'];
        }

        $memberDetails = ! empty($request->members)
            ? collect($request->members)->map(fn ($m) => $this->prepareMemberDetailPayload($m))->all()
            : [$this->prepareMemberDetailPayload($request->all())];

        $dataArray = [
            'quoteUID' => $quoteId,
            'memberDetails' => $memberDetails,
            'userId' => auth()->user()->id,
            'callSource' => strtolower(LeadSourceEnum::IMCRM),
        ];

        LoggerService::info('Health quote update member - Ken API request', extra: ['request' => $dataArray, 'uuid' => $quoteId]);

        $response = Ken::request('/update-health-quote-members', 'POST', $dataArray);

        LoggerService::info('Health quote update member - Ken API response', extra: ['response' => $response, 'uuid' => $quoteId]);

        return $response;
    }

    public function healthQuoteDeleteMember($request)
    {
        $quoteId = $request->quoteId ?? null;
        $memberId = $request->id ?? null;

        if ($quoteId && $memberId) {
            $memberDetails = [
                'id' => $memberId,
            ];

            $dataArray = [
                'quoteUID' => $quoteId,
                'memberDetails' => [$memberDetails],
                'userId' => auth()->user()->id,
                'callSource' => strtolower(LeadSourceEnum::IMCRM),
            ];

            LoggerService::info('Health quote delete member - Ken API request', extra: ['request' => $dataArray, 'uuid' => $quoteId]);

            $response = Ken::request('/delete-health-quote-members', 'POST', $dataArray);

            LoggerService::info('Health quote delete member - Ken API response', extra: ['response' => $response, 'uuid' => $quoteId]);

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

        $isAUH = $quote->isAUHLead(false);

        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150, 'isRemoteEnabled' => true])
            ->loadView('pdf.health_quote_plans', compact('quotePlans', 'planIds', 'quote', 'addons', 'providers', 'isAUH'));

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
                    'payment_gateway_id' => $payment->payment_gateway_id,
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
        $paymentGatewayEndpoint = PaymentGatewayEnum::getName($data['payment_gateway_id']);
        LoggerService::info('Payment code: '.$data['uuid'].' Payment Gateway Endpoint: '.$paymentGatewayEndpoint);
        $apiEndPoint = config('constants.MARSHALL_API_ENDPOINT').'/payment/'.$paymentGatewayEndpoint.'/cancel';
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
        LoggerService::info('inside the check for manual assignment QA');
        $lead->advisor_id = $userId;
        $lead->assignment_type = AssignmentTypeEnum::MANUAL_ASSIGNED;
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

    /**
     * Assign support user (OE/AE) to Health leads
     */
    public function assignSupportUser(array $leadIds, int $supportUserId, string $modelType): ?string
    {
        $updatedLeadIds = [];

        foreach ($leadIds as $leadId) {
            // Remove any type suffix if present (e.g., "123|health" -> "123")
            $id = explode('|', $leadId)[0];

            // Get the quote object using the trait method
            $quote = $this->getQuoteObject(QuoteTypes::HEALTH->value, $id);
            if ($quote) {
                // For Health, support user maps to WCU
                $quote->support_user_id = $supportUserId;
                $quote->save();
                $updatedLeadIds[] = $id;
            }
        }

        // Send a single email for all assigned leads
        if (! empty($updatedLeadIds) && $supportUserId) {
            try {
                $quoteType = QuoteTypes::from(ucfirst($modelType));
                SendSupportUserAssignmentEmailJob::dispatch(
                    Auth::id(),
                    $supportUserId,
                    $updatedLeadIds,
                    $quoteType
                )->delay(now()->addSeconds(5));
            } catch (\Exception $e) {
                LoggerService::error('Failed to dispatch support user assignment email job. Message: '.$e->getMessage(), [
                    'support_user_id' => $supportUserId,
                    'lead_ids' => $updatedLeadIds,
                    'model_type' => $modelType,
                ]);
            }
        }

        if (! empty($updatedLeadIds)) {
            $supportUserName = User::findOrFail($supportUserId)->name;

            return $modelType.' Leads has been Assigned To '.$supportUserName;
        }

        return null;
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
            ->leftJoin('payments as py', 'py.code', '=', 'q.code')
            ->leftJoin('quote_status as qs', 'q.quote_status_id', '=', 'qs.id')
            ->leftJoin('payment_status as ps', 'py.payment_status_id', '=', 'ps.id')
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

    /**
     * Whether the user may edit plan despite {@see HealthQuote::$is_quote_locked}
     * when the quote is transaction-approved, the user has {@see PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL},
     * the first pre-loaded payment has splits loaded, {@see Payment::$total_payments} matches the split row count,
     * and every split uses insurer payment ({@see PaymentMethodsEnum::InsurerPayment}).
     *
     * @param  iterable<int, Payment>|null  $payments  Pre-loaded payments for the quote (e.g. from CRUD show). When null, resolves via {@see HealthQuote::payments()} when $quote is a {@see HealthQuote}.
     */
    public function canBypassPlanLock(object $quote, ?iterable $payments = null): bool
    {
        $hasEligibleQuoteStatusForBypass = $quote->quote_status_id == QuoteStatusEnum::TransactionApproved;
        $userCanEditPlanAfterTransactionApproval = auth()->user()->can(PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL);

        $firstPayment = collect($payments)->first();
        $mainPayment = $hasEligibleQuoteStatusForBypass && $userCanEditPlanAfterTransactionApproval && $firstPayment instanceof Payment
            ? $firstPayment
            : null;

        $qualifiesByInsurerOnlySplits = $this->hasInsurerOnlySplits($mainPayment);

        return $userCanEditPlanAfterTransactionApproval && $qualifiesByInsurerOnlySplits;
    }

    private function hasInsurerOnlySplits(?Payment $mainPayment): bool
    {
        if (! $mainPayment) {
            return false;
        }

        $paymentSplits = $mainPayment->paymentSplits;
        if ($paymentSplits === null || $paymentSplits->isEmpty()) {
            return false;
        }

        $splitCount = $paymentSplits->count();

        $expectedSplitCount = $mainPayment->total_payments;
        if ($expectedSplitCount === null || (int) $expectedSplitCount !== $splitCount) {
            return false;
        }

        return $paymentSplits->where('payment_method', PaymentMethodsEnum::InsurerPayment)->count() === $splitCount;
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

    public function updateHealthData($quote, $request, $isEntity): void
    {
        if (! $quote) {
            LoggerService::info('Quote not found', extra: [
                'function' => __FUNCTION__,
            ]);

            return;
        }

        if ($isEntity) {
            $quote->emirate_of_your_visa_id = $request->emirate_of_registration_id;
        } else {
            $principalMember = $quote->activeMembers
                ->where('is_principal', 1)
                ->where('customer_type', CustomerTypeEnum::Individual)
                ->first();
            if ($principalMember) {
                $quote->emirate_of_your_visa_id = $principalMember->emirate_of_your_visa_id;
            }
        }

        $quote->save();
    }
}
