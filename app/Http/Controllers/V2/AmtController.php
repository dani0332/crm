<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TeamTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\ApplicationStorage;
use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\Emirate;
use App\Models\GroupMedicalType;
use App\Models\HealthPlanType;
use App\Models\KycLog;
use App\Models\Lookup;
use App\Models\LostReasons;
use App\Models\Nationality;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Repositories\ActivityRepository;
use App\Repositories\BusinessQuoteRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\AMLService;
use App\Services\BranchAssignmentService;
use App\Services\BusinessQuoteService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\GroupMedical\GroupMedicalAmtFormDropdownService;
use App\Services\GroupMedicalEcommerceJourneyLinkService;
use App\Services\GroupMedicalQuoteCategoryService;
use App\Services\HealthPlanTypeService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\RolePermissionConditions;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class AmtController extends Controller
{
    use GenericQueriesAllLobs, RolePermissionConditions,TeamHierarchyTrait;

    public function __construct(private HealthPlanTypeService $healthPlanTypeService) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = DB::table('business_quote_request as bqr')
            ->leftJoin('business_quote_request_detail as bqrd', 'bqr.id', '=', 'bqrd.business_quote_request_id')
            ->leftJoin('business_type_of_insurance as bit', 'bqr.business_type_of_insurance_id', '=', 'bit.id')
            ->leftJoin('users as u', 'bqr.advisor_id', '=', 'u.id')
            ->leftJoin('users as su', 'bqr.support_user_id', '=', 'su.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'bqrd.lost_reason_id')
            ->leftJoin('quote_status as qs', 'bqr.quote_status_id', '=', 'qs.id')
            ->leftJoin('payments as py', 'py.code', '=', 'bqr.code')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'py.payment_status_id')
            ->leftJoin('lookups as lss', 'lss.id', '=', 'bqr.sub_source_id')
            ->leftJoin('renewal_batches as rb', 'rb.id', '=', 'bqr.renewal_batch_id')
            ->leftJoin('user_branches as ub', function ($join) {
                $join->on('ub.user_id', '=', 'bqr.advisor_id')
                    ->where('ub.is_primary', '=', 1);
            })
            ->leftJoin('branches as b', 'b.id', '=', 'bqr.branch_id')
            ->leftJoin('emirates as e', 'bqr.emirate_of_registration_id', '=', 'e.id')
            ->leftJoin('users as pqa_u', 'pqa_u.id', '=', 'bqr.pq_advisor_id')
            ->leftJoin('users as lg', 'lg.id', '=', 'bqr.lead_generator_id')
            ->where('bit.text', '=', quoteStatusCode::GROUP_MEDICAL)
            ->select(
                'bqr.id',
                'bqr.code',
                'bqr.uuid as uuid',
                'bqr.first_name',
                'bqr.last_name',
                'bqr.assignment_type',
                'qs.text as leadStatus',
                DB::raw('DATE_FORMAT(bqr.created_at, "%d-%b-%Y %r") as created_at'),
                DB::raw('DATE_FORMAT(bqr.updated_at, "%d-%b-%Y %r") as updated_at'),
                'bit.text as leadType',
                'bqr.advisor_id',
                'bqr.support_user_id',
                'bqr.source',
                'ls.text as lost_reason',
                'u.name as advisor_id_text',
                'su.name as support_user_name',
                'pqa_u.name as pre_qualification_advisor_name',
                'bqr.premium',
                'bqr.company_name',
                DB::raw('DATE_FORMAT(bqrd.next_followup_date, "%d-%m-%Y") as next_followup_date'),
                DB::raw('DATE_FORMAT(bqrd.advisor_assigned_date, "%d-%b-%Y %r") as advisor_assigned_date'),
                'bqr.policy_number',
                'bqr.renewal_batch',
                'rb.name as renewal_batch_text',
                'rb.id as renewal_batch_id',
                'bqr.renewal_import_code',
                'bqr.previous_quote_policy_number',
                DB::raw('DATE_FORMAT(bqr.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
                'bqr.device',
                'bqr.previous_quote_policy_premium',
                'bqr.customer_id',
                'bqr.parent_duplicate_quote_id',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                'ps.text AS payment_status_id_text',
                'bqr.sub_source_id',
                DB::raw('lss.text as sub_source_text'),
                DB::raw('
                    CASE
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningPending.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningPending).'"
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningCleared.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningCleared).'"
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningFailed.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningFailed).'"
                        WHEN insurer_aml_status IS NULL THEN "'.AMLStatusCode::InsurerAMLScreeningNA.'"
                        ELSE insurer_aml_status
                    END AS insurer_aml_status_display
                '),
                'ub.branch_id as advisor_primary_branch_id',
                'b.name as lead_branch_name',
                'bqr.is_branch_applicable',
                'bqr.emirate_of_registration_id',
                'e.text as emirate_of_registration_text',
                DB::raw('(CASE
                    WHEN bqr.assignment_type = '.AssignmentTypeEnum::SYSTEM_ASSIGNED.' THEN "System Assigned"
                    WHEN bqr.assignment_type = '.AssignmentTypeEnum::SYSTEM_REASSIGNED.' THEN "System Reassigned"
                    WHEN bqr.assignment_type = '.AssignmentTypeEnum::MANUAL_ASSIGNED.' THEN "Manual Assigned"
                    WHEN bqr.assignment_type = '.AssignmentTypeEnum::MANUAL_REASSIGNED.' THEN "Manual Reassigned"
                    WHEN bqr.assignment_type = '.AssignmentTypeEnum::BOUGHT_LEAD.' THEN "Bought Lead"
                    WHEN bqr.assignment_type = '.AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD.' THEN "Reassigned as Bought Lead"
                    WHEN bqr.assignment_type = '.AssignmentTypeEnum::SELF_ASSIGNED.' THEN "Self Assigned"
                    ELSE "" END) as assignment_type_text'),
                'bqr.pq_advisor_id',
                'bqr.ea_model',
                'lg.name as lead_generator_name',
                'bqr.number_of_employees',
                'bqr.health_plan_type_id'
            );
        // PQA-only users see leads where they are the assigned pre-qualification advisor.
        // We skip the generic whereBasedOnRole for these users because isAdvisor() would
        // otherwise incorrectly restrict to advisor_id instead of pq_advisor_id.
        $isPqaOnly = Auth::user()->hasRole(RolesEnum::PreQualificationAdvisor)
            && ! Auth::user()->hasAnyRole([RolesEnum::GMAdvisor, RolesEnum::GMManager, RolesEnum::Admin, RolesEnum::Engineering]);

        if ($isPqaOnly) {
            $data->where('bqr.pq_advisor_id', Auth::user()->id);
        } else {
            if (Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::Business) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::Amt) || Auth::user()->isSpecificTeamAdvisor(quoteTypeCode::GM)) {
                // if user has advisor Role then fetch leads assigned to the user only
                $data->where('bqr.advisor_id', Auth::user()->id); // fetch leads assigned to the user
            }
            $this->whereBasedOnRole($data, 'bqr', quoteTypeCode::Business);
        }
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Business);

        $advisors = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['GM_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))->orderBy('r.name')->distinct()->get();

        // Fetch PQA
        $pqas = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', RolesEnum::PreQualificationAdvisor)
            ->select('u.id', 'u.name')
            ->orderBy('u.name')
            ->distinct()
            ->get();

        // Get support users (OE role with Group Medical product access)
        $supportUsers = app(UserService::class)->getSupportUsers([
            'product_filter' => QuoteTypes::GROUP_MEDICAL,
            'include_role_in_name' => true,
            'return_format' => 'collection',
        ]);

        $preQualificationAdvisors = User::activeUser()
            ->select(
                'users.id',
                DB::raw("CONCAT(users.name, ' - ', '".RolesEnum::PreQualificationAdvisor."') AS name"),
            )
            ->join('model_has_roles as pqa_mr', 'pqa_mr.model_id', '=', 'users.id')
            ->join('roles as pqa_r', 'pqa_r.id', '=', 'pqa_mr.role_id')
            ->join('pqa_lead_allocation_config as pqa_cfg', 'pqa_cfg.user_id', '=', 'users.id')
            ->where('pqa_mr.model_type', User::class)
            ->where('pqa_r.name', RolesEnum::PreQualificationAdvisor)
            ->where('pqa_cfg.quote_type_id', QuoteTypes::BUSINESS->id())
            ->orderBy('users.name')
            ->distinct()
            ->get();

        $isManagerORDeputy = Auth::user()->isManagerORDeputy();

        /* Check all conditions for client support assignment */
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SUPPORT_USER_ASSIGNMENT);

        $canAssignClientSupport = Auth::user()->can(PermissionsEnum::ASSIGN_CLIENT_SUPPORT) &&
                                 Auth::user()->hasRole(RolesEnum::CLIENTSUPPORTLEAD) &&
                                 Auth::user()->hasProduct(QuoteTypes::GROUP_MEDICAL->value);

        $model = 'Business';
        $insurerAMLStatus = AMLService::getInsurerAMLStatuses();

        if (! isset($request->code) && ! isset($request->email) && ! isset($request->mobile_no) && ! isset($request->created_at_start) && ! isset($request->payment_due_date) && ! isset($request->booking_date) && ! isset($request->company_name) && ! isset($request->insurer_tax_invoice_number) && ! isset($request->insurer_commission_tax_invoice_number) && ! isset($request->emirate_of_registration_id)) {
            $data->whereBetween('bqr.created_at', [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()]);
        }
        if (isset($request->company_name)) {
            $data->where('bqr.company_name', 'like', '%'.$request->company_name.'%');
        }

        if (
            empty($request->email) && empty($request->code) && empty($request->first_name) &&
            empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no) && empty($request->renewal_batch) && empty($request->previous_quote_policy_number)
        ) {
            $data->where('bqr.quote_status_id', '!=', QuoteStatusEnum::Fake);
        }

        if (isset($request->first_name) && $request->first_name != '') {
            $data->where('bqr.first_name', 'like', '%'.$request->first_name.'%');
        }
        if (isset($request->created_at_start) && $request->created_at_start != ''
        && isset($request->created_at_end)
        && $request->created_at_end != ''
        && empty($request->email)
        && empty($request->code)
        && empty($request->renewal_batch)
        && empty($request->payment_due_date)
        && empty($request->booking_date)
        && ! isset($request->previous_quote_policy_number)
        && ! isset($request->insurer_tax_invoice_number)
        && ! isset($request->insurer_commission_tax_invoice_number)
        ) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['created_at_start']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['created_at_end']));
            $data->whereBetween('bqr.created_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->policy_expiry_date) && $request->policy_expiry_date != '' && isset($request->policy_expiry_date_end) && $request->policy_expiry_date_end != '') {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['policy_expiry_date']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['policy_expiry_date_end']));
            $data->whereBetween('bqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->last_name) && $request->last_name != '') {
            $data->where('bqr.last_name', 'like', '%'.$request->last_name.'%');
        }
        if (isset($request->email) && $request->email != '') {
            $data->where('bqr.email', '=', $request->email);
        }
        if (isset($request->code) && $request->code != '') {
            $data->where('bqr.code', '=', $request->code);
        }
        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $data->where('bqr.mobile_no', '=', $request->mobile_no);
        }
        if (isset($request->leadStatus) && $request->leadStatus != '') {
            $data->whereIn('qs.id', $request->leadStatus);
        }
        if (isset($request->advisor_id) && is_array($request->advisor_id) && count($request->advisor_id) > 0) {
            if (count($request->advisor_id) === 1 && $request->advisor_id[0] == '-1') {
                $data->whereNull('bqr.advisor_id');
            } else {
                $data->whereIn('bqr.advisor_id', $request->advisor_id);
            }
        }
        if (isset($request->pq_advisor_id) && is_array($request->pq_advisor_id)) {
            $data->whereIn('bqr.pq_advisor_id', $request->pq_advisor_id);
        }

        if (isset($request->support_user_id) && is_array($request->support_user_id) && count($request->support_user_id) > 0) {
            if (count($request->support_user_id) === 1 && $request->support_user_id[0] == '-1') {
                $data->whereNull('bqr.support_user_id');
            } else {
                $data->whereIn('bqr.support_user_id', $request->support_user_id);
            }
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '' && isset($request->previous_policy_expiry_date_end) && $request->previous_policy_expiry_date_end != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->previous_policy_expiry_date)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->previous_policy_expiry_date_end)->endOfDay()->toDateTimeString();
            $data->whereBetween('bqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $data->where('bqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
            $data->where(function ($query) use ($request) {
                $query->where('bqr.policy_number', $request->previous_quote_policy_number)
                    ->orWhere('bqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            });
        }
        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $data->where('rb.name', $request->renewal_batch);
        }
        if ($request->filled('ea_model')) {
            $data->where('bqr.ea_model', $request->ea_model);
        }
        if ($request->filled('lead_generator')) {
            $data->where('lg.name', 'like', '%'.$request->lead_generator.'%');
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && $request->has('insurer_tax_invoice_number')) {
            $data->where('py.insurer_tax_number', $request->insurer_tax_invoice_number);
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && $request->has('insurer_commission_tax_invoice_number')) {
            $data->where('py.insurer_commmission_invoice_number', $request->insurer_commission_tax_invoice_number);
        }

        if (! empty($request->insurer_aml_status) && is_array($request->insurer_aml_status)) {
            $data->whereIn('bqr.insurer_aml_status', $request->insurer_aml_status);
        }

        if (isset($request->emirate_of_registration_id) && $request->emirate_of_registration_id !== '') {
            $ids = BusinessQuoteRepository::normalizeEmirateOfRegistrationIds($request->emirate_of_registration_id);
            if ($ids !== []) {
                $data->whereIn('bqr.emirate_of_registration_id', $ids);
            }
        }

        if (isset($request->advisor_assigned_date) && $request->advisor_assigned_date != '') {
            $dateArray = $request->advisor_assigned_date;
            $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
            $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
            $data->whereBetween('bqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }

        if ($request->filled('assignment_type') && strtolower((string) $request->assignment_type) !== 'all') {
            $data->where('bqr.assignment_type', $request->assignment_type);
        }
        if ($request->filled('pq_advisor_id')) {
            $data->whereIn('bqr.pq_advisor_id', $request->pq_advisor_id);
        }

        // Apply authorize_date filter
        if (! empty($request->authorize_date) && is_array($request->authorize_date) && count($request->authorize_date) >= 2) {
            $startDate = Carbon::parse($request->authorize_date[0])->startOfDay();
            $endDate = Carbon::parse($request->authorize_date[1])->endOfDay();
            $data->whereBetween('py.authorized_at', [$startDate, $endDate]);
        }

        // Apply captured_date filter
        if (! empty($request->captured_date) && is_array($request->captured_date) && count($request->captured_date) >= 2) {
            $startDate = Carbon::parse($request->captured_date[0])->startOfDay();
            $endDate = Carbon::parse($request->captured_date[1])->endOfDay();
            $data->whereBetween('py.captured_at', [$startDate, $endDate]);
        }

        $this->adjustQueryByDateFilters($data, 'bqr');

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 11) {
                $column = 'bqr.created_at';
            }
            if ($column == 12) {
                $column = 'bqr.updated_at';
            }
            if ($column == 8) {
                $column = 'bqrd.next_followup_date';
            }
            $data->orderBy('bqr.advisor_id')->orderBy($column, $direction);
        } else {
            $data->orderBy('bqr.created_at', 'DESC')->orderBy('bqr.advisor_id');
        }
        $paymentAuthorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $authorizedDays = intval($paymentAuthorizedDays->value);

        $canAssignLeadAdvisor = auth()->user()->isAdmin() ||
            $isManagerORDeputy ||
            Auth::user()->can(PermissionsEnum::ASSIGN_LEAD_ADVISOR);

        $canAssignPreQualificationAdvisor = Auth::user()->can(PermissionsEnum::ASSIGN_GROUP_MEDICAL_PRE_QUALIFICATION_ADVISOR)
            || Auth::user()->hasAnyRole([RolesEnum::Admin, RolesEnum::Engineering, RolesEnum::LeadPool]);

        $isManualAllocationAllowed = ($canAssignLeadAdvisor || $canAssignClientSupport || $canAssignPreQualificationAdvisor);
        $quotes = $data->simplePaginate(15)->withQueryString();

        $this->postProcessAmtQuotes($quotes);

        $subSources = app(LookupService::class)->getSubSource();
        $emirates = Emirate::getActiveEmirates();
        $assignmentTypes = AssignmentTypeEnum::withLabels();

        $groupMedicalName = QuoteTypes::GROUP_MEDICAL->value;
        $productType = TeamTypeEnum::PRODUCT;

        $preQualificationAdvisors = User::activeUser()
            ->select(
                'users.id',
                DB::raw("CONCAT(users.name, ' - ', '".RolesEnum::PreQualificationAdvisor."') AS name"),
            )
            ->join('model_has_roles as pqa_mr', 'pqa_mr.model_id', '=', 'users.id')
            ->join('roles as pqa_r', 'pqa_r.id', '=', 'pqa_mr.role_id')
            ->join('pqa_lead_allocation_config as pqa_cfg', 'pqa_cfg.user_id', '=', 'users.id')
            ->where('pqa_mr.model_type', User::class)
            ->where('pqa_r.name', RolesEnum::PreQualificationAdvisor)
            ->where('pqa_cfg.quote_type_id', QuoteTypes::BUSINESS->id())
            ->whereExists(function ($sub) use ($productType, $groupMedicalName) {
                $sub->selectRaw('1')
                    ->from('user_products as up_gm')
                    ->join('teams as t_gm', 't_gm.id', '=', 'up_gm.product_id')
                    ->whereColumn('up_gm.user_id', 'users.id')
                    ->where('t_gm.type', $productType)
                    ->whereRaw("UPPER(t_gm.name) = UPPER('{$groupMedicalName}')");
            })
            ->orderBy('users.name')
            ->distinct()
            ->get();

        return inertia('GroupMedicalQuote/Index', compact('model', 'pqas', 'leadStatuses', 'advisors', 'supportUsers', 'canAssignClientSupport', 'canAssignLeadAdvisor', 'isManagerORDeputy', 'quotes', 'isManualAllocationAllowed', 'authorizedDays', 'insurerAMLStatus', 'subSources', 'emirates', 'assignmentTypes', 'canAssignPreQualificationAdvisor', 'preQualificationAdvisors'));
    }

    /**
     * Post-process AMT quotes to add branch name information.
     * Uses eager loading to avoid N+1 query issues.
     *
     * @param  Paginator  $quotes
     * @return Paginator
     */
    private function postProcessAmtQuotes($quotes)
    {
        // Map through quotes and add branch_name using pre-loaded data
        return $quotes->map(function ($quote) {
            $emirateOfRegistrationId = $quote?->emirate_of_registration_id ?? null;
            $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote->lead_branch_name ?? app(BranchAssignmentService::class)->getBranchName($quote->advisor_primary_branch_id, QuoteTypeId::GroupMedical, $emirateOfRegistrationId));
            $quote->plan_type_text = $this->healthPlanTypeService->getById($quote->health_plan_type_id);

            return $quote;
        });
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response|ResponseFactory
     */
    public function create(Request $request)
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();

        $subSources = app(LookupService::class)->getSubSource();
        $emirates = Emirate::getActiveEmirates();

        return inertia('GroupMedicalQuote/Form', [
            'businessInsuranceType' => $businessInsuranceType,
            'quote' => new BusinessQuote,
            'subSources' => $subSources,
            'emirates' => $emirates,
            ...app(GroupMedicalAmtFormDropdownService::class)->formDropdownProps(),
            'leadSourceParams' => [
                'type' => $request->input('type'),
                'subSource' => $request->input('subSourceId'),
                'subSourceOption' => $request->input('subSourceOptionsId'),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->merge([
            'number_of_employees' => collect($request->input('categories', []))->sum(fn ($row) => (int) ($row['numberOfPeople'] ?? 0)),
        ]);

        $this->validate($request, array_merge([
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            'email' => 'required|email:rfc,dns|max:150',
            'mobile_no' => 'required|regex:/(0)[0-9]/|not_regex:/[a-z]/|min:7|max:20',
            'business_type_of_insurance_id' => 'required',
            'company_name' => 'required|max:150',
            'number_of_employees' => 'required|numeric|min:1|max:2147483645',
            'brief_details' => 'required',
            'emirate_of_registration_id' => 'required|exists:emirates,id',
        ], $this->groupMedicalAmtIntakeValidationRules()));

        $record = app(BusinessQuoteService::class)->saveBusinessQuote($request);
        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return Redirect::back()->with('message', $record->message)->withInput();
        } else {
            if (! isset($record->quoteUID)) {
                return redirect('medical/amt')->with('success', 'Lead has been stored');
            } else {
                return redirect('medical/amt/'.$record->quoteUID)->with('success', 'Lead has been stored');
            }
        }
    }

    /**
     * @param  $uuid
     * @return Response|ResponseFactory
     */
    public function show($id)
    {

        $crudService = app(CRUDService::class);

        if (! $this->checkUserHasGroupMedicalAccess('show')) {
            abort(403, 'Unauthorized access');
        }

        $record = BusinessQuoteRepository::getBy([
            'uuid' => $id,
            'business_type_of_insurance_id' => quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical),
        ])->load([
            'subSource:id,text,description',
            'subSourceOption:id,text,description',
            'renewalBatchModel:id,name',
            'groupMedicalType:id,text,description',
            'natureOfCompanyActivity:id,text',
            'groupMedicalCategories',
            'businessActivity:id,name',
            'leadGenerator',
            'expertAdvisor',
        ]);
        abort_if(! $record, 404);
        /* Start - Temporarily adding for correcting historic data */
        (new PaymentRepository)->updatePriceVatApplicableAndVat($record, QuoteTypes::BUSINESS->value);
        /* End - Temporarily adding for correcting historic data */

        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::BUSINESS->value, $record);
        $companyType = Lookup::getCompanyTypes();
        $data = $record->toArray();
        $record->lost_reason = $data['business_quote_request_detail']['lost_reason']['text'] ?? null;
        $record->previous_advisor_id_text = $data['previous_advisor']['name'] ?? null;
        $record->transaction_type_text = $data['transaction_type']['text'] ?? null;
        $record->health_plan_type_text = ! empty($record->health_plan_type_id)
            ? (HealthPlanType::find($record->health_plan_type_id)?->text ?? null)
            : null;
        $gmDropdownService = app(GroupMedicalAmtFormDropdownService::class);
        $gmCategoryIntakeDisplay = $record->groupMedicalCategories->isNotEmpty()
            ? $gmDropdownService->enrichCategoryIntakeForDisplay($record->groupMedicalCategories)
            : $gmDropdownService->enrichCategoryIntakeFromJson($record->gm_category_intake ?? []);
        $quoteDetails = app(BusinessQuoteService::class)->getDetailEntity($record->id);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::BUSINESS->id())->get();
        $lostReasons = LostReasons::getAll();
        $allowedDuplicateLOB = $crudService->getAllowedDuplicateLOB('Group Medical', $record->code);
        $customerAdditionalContacts = app(CustomerService::class)->getAdditionalContacts($record->customer_id, $record->mobile_no);
        $UBODetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::BUSINESS->name, CustomerTypeEnum::Entity);
        $membersDetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::BUSINESS->name);
        $memberRelations = Lookup::getMemberRelations();
        $nationalities = Nationality::getActiveNationalities();
        $UBORelations = Lookup::getUBORelations();
        $emirates = Emirate::getActiveEmirates();

        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($record, QuoteTypes::BUSINESS->id(), $quoteStatuses);
        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::BUSINESS->id(), $record->insurance_provider_id);
        $amlQuoteStatus = $crudService->checkAmlQuoteStatus($record->quote_status_id);
        $lookupService = app(LookupService::class);
        $paymentMethods = $lookupService->getPaymentMethods();
        $legalStructure = $lookupService->getLegalStructure();
        $idDocumentType = $lookupService->getEntityDocumentTypes();
        $issuancePlace = $lookupService->getIssuancePlaces();
        $issuanceAuthorities = $lookupService->getIssuanceAuthorities();
        $latestKycLog = KycLog::withTrashed()
            ->where('quote_request_id', $record->id)
            ->where('quote_type_id', QuoteTypes::BUSINESS->id())
            ->standardAmlFilters()
            ->latest()->first();
        @[$documentTypes, $paymentDocuments] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypes::BUSINESS->id(), $record?->business_type_of_insurance_id, $latestKycLog?->search_type, quoteTypeCode::GroupMedical);
        $vatPercentage = getAppStorageValueByKey(ApplicationStorageEnums::VAT_VALUE, useCache: true, default: 0);

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = $crudService->hasAtleastOneStatusPolicyIssued($record);

        if ($hasPolicyIssuedStatus) {
            $removeOptions = [
                // Endorsement Financial.
                SendUpdateLogStatusEnum::AOLOPFMP,
                SendUpdateLogStatusEnum::AC,
                SendUpdateLogStatusEnum::AL,
                SendUpdateLogStatusEnum::EA,
                SendUpdateLogStatusEnum::ED,
                SendUpdateLogStatusEnum::EFMP,
                SendUpdateLogStatusEnum::I_CLILLR,
                SendUpdateLogStatusEnum::IEAF_T,
                SendUpdateLogStatusEnum::IISI,
                SendUpdateLogStatusEnum::PPE,
                // Endorsement non Financial.
                SendUpdateLogStatusEnum::AAI,
                SendUpdateLogStatusEnum::AOC,
                SendUpdateLogStatusEnum::COA,
            ];

            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::BUSINESS->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($record->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $isQuoteDocumentEnabled = app(QuoteDocumentService::class)->isEnabled(QuoteTypes::BUSINESS->value);
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::BUSINESS->value, $record->id);
        $bookPolicyDetails = $this->bookPolicyPayload($record, QuoteTypes::GROUP_MEDICAL->value, $record->payments, $quoteDocuments);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($record);
        $amlStatusName = AMLStatusCode::getName($record->aml_status);

        $isEmirateOfRegistrationLocked = app(CentralService::class)->isEmirateOfRegistrationLocked($record, quoteTypeCode::Business);

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::BUSINESS->id(),
            'quote_request_id' => $record->id,
        ])
            ->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();

        $advisors = User::role(RolesEnum::GMAdvisor)
            ->select('users.id', DB::raw("CONCAT(users.name, ' - ', '".RolesEnum::GMAdvisor."') AS name"))
            ->get();

        if (auth()->user()->hasRole(RolesEnum::PreQualificationAdvisor)) {
            $quoteStatuses = array_values(QuoteStatus::whereIn('id', [QuoteStatusEnum::FollowedUp, QuoteStatusEnum::MissingDocumentsRequested, $record->quote_status_id])->get()->toArray());
        }

        return inertia('GroupMedicalQuote/Show', [
            'documentTypes' => $documentTypes,
            'amlQuoteStatus' => $amlQuoteStatus,
            'legalStructure' => $legalStructure,
            'idDocumentType' => $idDocumentType,
            'issuancePlace' => $issuancePlace,
            'issuanceAuthorities' => $issuanceAuthorities,
            'quoteType' => quoteTypeCode::Business,
            'quote' => $record,
            'amlStatusName' => $amlStatusName,
            'quoteDetails' => $quoteDetails,
            'quoteTypeId' => QuoteTypeId::Business,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'genderOptions' => $crudService->getGenderOptions(),
            'typeCode' => quoteTypeCode::GroupMedical,
            'lostReasons' => $lostReasons,
            'quoteStatuses' => $quoteStatuses,
            'modelType' => QuoteTypes::BUSINESS,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::GMManager),
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'companyTypes' => $companyType,
            'UBOsDetails' => $UBODetails,
            'UBORelations' => $UBORelations,
            'membersDetails' => $membersDetails,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'insuranceProviders' => $insuranceProviders,
            'vatPercentage' => $vatPercentage,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'paymentMethods' => $paymentMethods,
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($record->payments),
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'sendUpdateEnum' => $sendUpdateEnum,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'record' => fn () => $record,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],
            'bookPolicyDetails' => $bookPolicyDetails,
            'payments' => $record?->payments,
            'isEmirateOfRegistrationLocked' => $isEmirateOfRegistrationLocked,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocuments,
            'paymentGatewayEnum' => PaymentGatewayIdEnum::asArray(),
            'isFuncsEnabled' => ['tapIntegration' => isTapEnabled()],
            'activities' => $activities,
            'advisors' => $advisors,
            'gmEcommerceCopyLink' => [
                'enabled' => app(GroupMedicalEcommerceJourneyLinkService::class)->isAdvisorCopyEnabled($record),
            ],
            'gmCategoryIntakeDisplay' => $gmCategoryIntakeDisplay,
        ]);
    }

    public function copyEcommerceJourneyLink(
        string $uuid,
        GroupMedicalEcommerceJourneyLinkService $groupMedicalEcommerceJourneyLinkService,
    ): JsonResponse {
        $quote = BusinessQuote::query()
            ->where('uuid', $uuid)
            ->where('business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL)
            ->first();

        if (! $quote) {
            abort(404);
        }

        if (! $groupMedicalEcommerceJourneyLinkService->isAdvisorCopyEnabled($quote)) {
            return response()->json([
                'message' => 'Copy Link is unavailable after the lead is Transaction Approved.',
            ], 422);
        }

        $url = $groupMedicalEcommerceJourneyLinkService->buildCustomerJourneyUrl($quote);
        if ($url === null) {
            return response()->json([
                'message' => 'Group Medical ecommerce URL is not configured.',
            ], 503);
        }

        $groupMedicalEcommerceJourneyLinkService->recordAdvisorCopyLinkAudit($quote, Auth::user());

        return response()->json(['url' => $url]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \\Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($id)
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();
        $record = BusinessQuote::with(['quoteRequestEntityMapping.entity', 'groupMedicalCategories.category'])
            ->where([['uuid', $id], ['business_type_of_insurance_id', 5]])
            ->first();

        abort_if(! $record, 404);

        $entityEmirateOfRegistrationId = $record->quoteRequestEntityMapping?->entity?->emirate_of_registration_id ?? null;
        if ($entityEmirateOfRegistrationId) {
            $record->emirate_of_registration_id = $entityEmirateOfRegistrationId;
        }

        $gmTypes = GroupMedicalType::select('id', 'text', 'description')->get();
        $GMType = DB::table('business_quote_request')
            ->join('group_medical_types as gmt', 'business_quote_request.group_medical_type_id', '=', 'gmt.id')
            ->where('business_quote_request.uuid', $id)
            ->select('gmt.text as text', 'gmt.id as id')
            ->first();
        $selectedGmType = '';
        if (! is_null($GMType)) {
            $selectedGmType = $GMType->id;
        }

        $subSources = app(LookupService::class)->getSubSource();
        $emirates = Emirate::getActiveEmirates();

        return inertia('GroupMedicalQuote/Form', [
            'businessInsuranceType' => $businessInsuranceType,
            'quote' => $record,
            'gmTypes' => $gmTypes,
            'selectedGmType' => $selectedGmType,
            'subSources' => $subSources,
            'emirates' => $emirates,
            'isEmirateDisabled' => true,
            'leadSourceParams' => [],
            'categoryCount' => app(GroupMedicalQuoteCategoryService::class)->getCategoryCountByQuoteId($record->id),
            ...app(GroupMedicalAmtFormDropdownService::class)->formDropdownProps(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->merge([
            'number_of_employees' => collect($request->input('categories', []))->sum(fn ($row) => (int) ($row['numberOfPeople'] ?? 0)),
        ]);

        $this->validate($request, array_merge([
            'first_name' => 'required|max:150',
            'last_name' => 'required|max:150',
            'business_type_of_insurance_id' => 'required',
            'company_name' => 'required|max:150',
            'number_of_employees' => 'required|numeric|min:1|max:2147483645',
            'brief_details' => 'required',
        ], $this->groupMedicalAmtIntakeValidationRules()));
        app(CRUDService::class)->updateModelByType('business', $request, $id);

        return redirect('medical/amt/'.$id)->with('success', 'Lead has been updated');
    }

    public function cardsView(Request $request)
    {
        $quotes = [];
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Business);

        $leadStatuses = $leadStatuses->filter(function ($item) {
            return $item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION || $item->text == quoteStatusCode::PAYMENTPENDING || $item->text == quoteStatusCode::APPLICATION_PENDING || $item->text == quoteStatusCode::POLICY_DOCUMENTS_PENDING || $item->text == quoteStatusCode::PAYMENT_LINK_SENT_TO_CUSTOMER || $item->text == quoteStatusCode::PaymentInitiated || $item->text == quoteStatusCode::TRANSACTIONAPPROVED;
        })->map(function ($item) use ($request) {
            $item->data = getDataAgainstStatus('Business', $item->id, $request);

            return $item;
        })->toArray();

        return inertia('GroupMedicalQuote/Cards', [
            'quotes' => array_values($leadStatuses),
            // 'quotes' => $quotes,
            'quoteStatusEnum' => [],
            'lostReasons' => [],
            'leadStatuses' => $leadStatuses,
            'advisors' => [],
            'teams' => [],
            'insuranceTypeOptions' => [],
            'quoteTypeId' => QuoteTypes::BUSINESS->id(),
            'quoteType' => QuoteTypes::BUSINESS->value,
            'totalCount' => 0,
            'areBothTeamsPresent' => false,
            'is_renewal' => null,
            'business_type_of_insurance_id' => BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL,
        ]);
    }

    public function getNetworksByTpa(Request $request): JsonResponse
    {
        $tpaId = $request->integer('tpa_id');

        if (! $tpaId) {
            return response()->json([]);
        }

        $networks = app(GroupMedicalAmtFormDropdownService::class)->groupMedicalNetworks($tpaId);

        return response()->json($networks);
    }

    /**
     * @return array<string, mixed>
     */
    protected function groupMedicalAmtIntakeValidationRules(): array
    {
        return [
            'nature_of_company_activity_id' => ['required', 'exists:business_activities,id'],
            'has_existing_group_health_insurance' => ['required', 'boolean'],
            'health_plan_type_id' => ['required', 'exists:health_plan_type,id'],
            'categories' => [
                'required',
                'array',
                'max:26',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $expected = (int) request()->input('number_of_categories', 0);
                    if (! is_array($value) || count($value) !== $expected) {
                        $fail('People per category rows must match the number of categories.');
                    }

                    $categoryIds = array_filter(array_column($value, 'groupMedicalCategoryId'));
                    if (count($categoryIds) !== count(array_unique($categoryIds))) {
                        $fail('Each category can only be selected once.');
                    }
                },
            ],
            // 'categories.*.groupMedicalCategoryId' => ['required', 'integer', 'exists:group_medical_category,id'],
            'categories.*.insuranceProviderId' => ['nullable', 'exists:insurance_provider,id'],
            'categories.*.healthTpaId' => ['nullable', 'exists:health_third_party_administrator,id'],
            'categories.*.healthNetworkId' => ['nullable', 'exists:health_networks,id'],
            'categories.*.renewalDate' => ['nullable', 'date'],
            'categories.*.numberOfPeople' => ['required', 'integer', 'min:1', 'max:2147483645'],
        ];
    }

    public function checkUserHasGroupMedicalAccess($method)
    {

        $permissions = [
            'show' => [PermissionsEnum::GMQuotesEdit, PermissionsEnum::GMQuotesCreate, PermissionsEnum::GMQuotesList],
            'edit' => [PermissionsEnum::GMQuotesEdit, PermissionsEnum::GMQuotesCreate],
            'create' => [PermissionsEnum::GMQuotesCreate],
            'list' => [PermissionsEnum::GMQuotesList],
        ];
        $roles = [
            'show' => [RolesEnum::GMManager, RolesEnum::GMDeputyManager, RolesEnum::GMAdvisor],
            'edit' => [RolesEnum::GMManager, RolesEnum::GMDeputyManager, RolesEnum::GMAdvisor],
            'create' => [RolesEnum::GMManager, RolesEnum::GMDeputyManager, RolesEnum::GMAdvisor],
            'list' => [RolesEnum::GMManager, RolesEnum::GMDeputyManager, RolesEnum::GMAdvisor],
        ];
        if (
            auth()->user()->canAny($permissions[$method])
            || auth()->user()->hasAnyRole(...$roles[$method])
            || auth()->user()->hasAnyRole(RolesEnum::Engineering, RolesEnum::Admin)
            || auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS)
        ) {
            return true;
        } else {
            return false;
        }
    }
}
