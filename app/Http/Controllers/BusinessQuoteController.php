<?php

namespace App\Http\Controllers;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\LookupsEnum;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TeamNameEnum;
use App\Events\LeadsCount;
use App\Http\Requests\StoreBusinessQuoteRequest;
use App\Http\Requests\UpdateBusinessQuoteRequest;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\DocumentType;
use App\Models\Emirate;
use App\Models\KycLog;
use App\Models\Lookup;
use App\Models\Nationality;
use App\Models\User;
use App\Repositories\BusinessQuoteRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuoteNoteRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\AMLService;
use App\Services\BranchAssignmentService;
use App\Services\BusinessQuoteService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Inertia\ResponseFactory;

class BusinessQuoteController extends Controller
{
    protected $businessQuoteService;
    protected $crudService;
    protected $lookupService;
    protected $genericModel;
    protected $dropdownSourceService;

    public const TYPE = quoteTypeCode::Business;
    public const TYPE_ID = QuoteTypeId::Business;

    use GenericQueriesAllLobs, RolePermissionConditions;

    public function __construct(
        BusinessQuoteService $businessQuoteService,
        CRUDService $crudService,
        LookupService $lookupService,
        DropdownSourceService $dropdownSourceService
    ) {
        $this->businessQuoteService = $businessQuoteService;
        $this->genericModel = $this->businessQuoteService->getGenericModel(self::TYPE);
        $this->crudService = $crudService;
        $this->lookupService = $lookupService;
        $this->dropdownSourceService = $dropdownSourceService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function index(Request $request)
    {
        $dropdownSource = $this->businessQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $dropdownSource['quote_status_id'] = collect($dropdownSource['quote_status_id'])->filter(function ($value) {
            return $value['id'] != QuoteStatusEnum::Lost;
        })->values();

        $gridData = $this->businessQuoteService->getGridData($this->genericModel, $request);
        // PD Revert
        // $count = $gridData->count();
        $count = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;
        $quotes = $gridData->simplePaginate(10)->withQueryString();
        $this->businessQuoteService->postProcessBusinessQuotes($quotes);
        $isManagerORDeputy = auth()->user()->isManagerORDeputy();

        // Support users and assignment permissions for LeadAssignment component
        $supportUsers = app(UserService::class)->getSupportUsers([
            'product_filter' => QuoteTypes::CORPLINE,
            'include_role_in_name' => true,
            'return_format' => 'collection',
        ]);

        $canAssignClientSupport = Auth::user()->can(PermissionsEnum::ASSIGN_CLIENT_SUPPORT)
            && Auth::user()->hasRole(RolesEnum::CLIENTSUPPORTLEAD)
            && Auth::user()->hasProduct(QuoteTypes::CORPLINE->value);

        $canAssignLeadAdvisor = auth()->user()->isAdmin()
            || $isManagerORDeputy
            || Auth::user()->can(PermissionsEnum::ASSIGN_LEAD_ADVISOR);

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
            ->whereIn('pqa_cfg.quote_type_id', [QuoteTypes::CORPLINE->id(), QuoteTypes::BUSINESS->id()])
            ->orderBy('users.name')
            ->distinct()
            ->get();

        $canAssignPreQualificationAdvisor = Auth::user()->can(PermissionsEnum::ASSIGN_GROUP_MEDICAL_PRE_QUALIFICATION_ADVISOR)
            || Auth::user()->hasAnyRole([RolesEnum::Admin, RolesEnum::Engineering, RolesEnum::LeadPool]);

        $isManualAllocationAllowed = ($canAssignLeadAdvisor || $canAssignClientSupport || $canAssignPreQualificationAdvisor);

        // PD Revert
        // $totalCount = count(request()->all()) > 1 || $hasOtherFilters ? $count : BusinessQuoteRepository::getData(quoteTypeCode::CORPLINE, true, true);
        $totalCount = 0;
        $paymentAuthorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $authorizedDays = intval($paymentAuthorizedDays->value);
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();
        $insurerAMLStatus = AMLService::getInsurerAMLStatuses();

        $subSources = app(LookupService::class)->getSubSource();
        $leadTypeOptions = $this->lookupService->getCorplineLeadTypes();

        return inertia('CorpLineQuote/Index', compact(
            'quotes',
            'renewalBatches',
            'dropdownSource',
            'isManualAllocationAllowed',
            'canAssignClientSupport',
            'canAssignLeadAdvisor',
            'canAssignPreQualificationAdvisor',
            'preQualificationAdvisors',
            'supportUsers',
            'totalCount',
            'authorizedDays',
            'insurerAMLStatus',
            'subSources',
            'leadTypeOptions'
        ));
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

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function create(Request $request)
    {
        $isRenewalUser = auth()->user()->isRenewalUser();

        $renewalAdvisors = $this->businessQuoteService->getRenewalAdvisors();
        $this->businessQuoteService->fillData();
        $dropdownSource = $this->businessQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $subSources = app(LookupService::class)->getSubSource();

        $model = $this->genericModel;

        return inertia('CorpLineQuote/Form', [
            'quote' => new BusinessQuote,
            'dropdownSource' => $dropdownSource,
            'renewalAdvisors' => $renewalAdvisors ?? [],
            'isRenewalUser' => $isRenewalUser,
            'subSources' => $subSources,
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
    public function store(StoreBusinessQuoteRequest $request)
    {
        $record = $this->businessQuoteService->saveBusinessQuote($request);

        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return redirect()->back()->with('message', $record->message)->withInput();
        } else {
            event(new LeadsCount(BusinessQuoteRepository::getData(quoteTypeCode::CORPLINE, true, true)));
            if (! isset($record->quoteUID)) {
                return redirect('quotes/business')->with('success', 'Lead has been stored');
            } else {
                return redirect('quotes/business/'.$record->quoteUID)->with('success', 'Lead has been stored');
            }
        }
    }

    /**
     * @param  $uuid
     * @return Response|ResponseFactory
     */
    public function show($id)
    {
        $quoteType = strtolower($this->genericModel->modelType);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        abort_if(! $record, 404);

        /* Start - Temporarily adding for correcting historic data */
        (new PaymentRepository)->updatePriceVatApplicableAndVat($record, $this->genericModel->modelType);
        /* End - Temporarily adding for correcting historic data */

        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::BUSINESS->value, $record);
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($quoteType, $record->code);
        $dropdownSource = $this->businessQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        $quoteDetails = $this->businessQuoteService->getDetailEntity($record->id);
        $this->businessQuoteService->fillData();

        $isQuoteDocumentEnabled = $this->businessQuoteService->quoteDocumentEnabled($this->genericModel->modelType);
        $quoteDocuments = $this->businessQuoteService->getQuoteDocuments($this->genericModel->modelType, $record->id);
        $displaySendPolicyButton = $this->businessQuoteService->displaySendPolicyButton($record, $quoteDocuments, self::TYPE_ID);
        $latestKycLog = KycLog::withTrashed()
            ->where('quote_request_id', $record->id)
            ->where('quote_type_id', QuoteTypes::BUSINESS->id())
            ->standardAmlFilters()
            ->latest()->first();
        @[$documentTypes, $paymentDocuments] = app(QuoteDocumentService::class)->getDocumentTypes(self::TYPE_ID, $record?->business_type_of_insurance_id, $latestKycLog?->search_type, quoteTypeCode::CORPLINE);
        $activities = $this->businessQuoteService->getActivityByLeadId($record->id, strtolower($this->genericModel->modelType));
        $customerAdditionalContacts = $this->businessQuoteService->getAdditionalContacts($record->customer_id, $record->mobile_no);

        $paymentEntityModel = $this->{strtolower($this->genericModel->modelType).'QuoteService'}->getEntityPlain($record->id);
        $paymentEntityModel->load('insuranceProviderDetails');
        $payments = $paymentEntityModel->payments;
        $paymentMethods = $this->lookupService->getPaymentMethods();

        $isNewPaymentStructure = app(SplitPaymentService::class)->isNewPaymentStructure($payments);
        if ($isNewPaymentStructure) {
            $filteredPaymentMethods = $this->lookupService->getPaymentMethods();
        } else {
            $filteredPaymentMethods = $paymentMethods->filter(function ($paymentMethod) {
                return $paymentMethod->code == PaymentMethodsEnum::CreditCard;
            })->map(function ($paymentMethod) {
                return [
                    'value' => $paymentMethod->code,
                    'label' => $paymentMethod->name,
                ];
            })->values();
        }

        $quoteStatuses = collect($dropdownSource['quote_status_id'])->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($record, self::TYPE_ID, $quoteStatuses);

        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Business, $record->insurance_provider_id);
        $companyType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $UBODetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::BUSINESS->name, CustomerTypeEnum::Entity);
        $membersDetail = CustomerMembersRepository::getBy($record->id, QuoteTypes::BUSINESS->name);
        $nationalities = Nationality::getActiveNationalities();
        $UBORelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();

        $filteredInsuranceProviders = [];
        if (! empty($insuranceProviders)) {
            $filteredInsuranceProviders = $insuranceProviders->map(function ($paymentMethod) {
                return [
                    'value' => $paymentMethod->id,
                    'label' => $paymentMethod->text,
                ];
            })->sortBy('label')->values();
        }
        $payments->load(['paymentStatus', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider']);

        $payments->each(function ($payment) {
            $allow = $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED && ! auth()->user()->hasRole(RolesEnum::PA);
            $payment->copy_link_button = $allow && optional($payment->paymentMethod)->code == PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID;
            $payment->edit_button = $allow && $payment->payment_status_id != PaymentStatusEnum::PAID;
            $payment->approve_button = optional($payment->paymentMethod)->code != PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID && $payment->payment_status_id != PaymentStatusEnum::CAPTURED
                && ! auth()->user()->hasRole(RolesEnum::PA);

            $payment->approved_button = $payment->payment_status_id == PaymentStatusEnum::PAID;
        });

        $quoteNotes = QuoteNoteRepository::getBy($record->id, QuoteTypes::BUSINESS->name);
        $noteDocumentType = DocumentType::where('code', DocumentTypeCode::OD)->first();
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = $this->crudService->hasAtleastOneStatusPolicyIssued($record);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::BUSINESS->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($record->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $bookPolicyDetails = $this->bookPolicyPayload($record, QuoteTypes::BUSINESS->value, $payments, $quoteDocuments);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($record);
        $record->branch_name = ! $record->is_branch_applicable ? 'N/A' : ($record->lead_branch_name ?? app(BranchAssignmentService::class)->getBranchName($record->advisor_primary_branch_id, QuoteTypeId::Business));
        // $record is a DB::table() stdClass — build relation-shaped arrays from the pre-joined columns.
        $record->lead_generator = $record->lead_generator_id
            ? ['id' => $record->lead_generator_id, 'name' => $record->lead_generator_name, 'email' => $record->lead_generator_email]
            : null;
        $record->expert_advisor = $record->expert_advisor_id
            ? ['id' => $record->expert_advisor_id, 'name' => $record->expert_advisor_name]
            : null;

        return inertia('CorpLineQuote/Show', [
            'storageUrl' => storageUrl(),
            'quoteType' => quoteTypeCode::Business,
            'quote' => $record,
            'amlStatusName' => AMLStatusCode::getName($record->aml_status),
            'quoteDetails' => $quoteDetails,
            'modelType' => $this->genericModel->modelType,
            'quoteTypeId' => QuoteTypeId::Business,
            'leadStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'quoteDocuments' => $quoteDocuments,
            'documentTypes' => $documentTypes,
            'memberCategories' => $this->lookupService->getMemberCategories(),
            'activities' => $activities,
            'customerAdditionalContacts' => $customerAdditionalContacts,
            'payments' => $payments,
            'quoteRequest' => $paymentEntityModel,
            'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
            'paymentMethods' => $filteredPaymentMethods,
            'insuranceProviders' => $filteredInsuranceProviders,
            'insuranceProvidersAll' => $insuranceProviders,
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'isManualAllocationAllowed' => auth()->user()->isAdmin() || auth()->user()->hasRole(RolesEnum::LeadPool) ? true : false,
                'notProductionApproval' => ! auth()->user()->hasRole(RolesEnum::PA),
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                'displaySendPolicyButton' => $displaySendPolicyButton,
                'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                'canNotEditPayments' => auth()->user()->cannot(PermissionsEnum::PaymentsEdit),
                'auditable' => auth()->user()->can(PermissionsEnum::Auditable),
                'canNotApprovePayments' => auth()->user()->cannot(PermissionsEnum::ApprovePayments),
                'canEditQuote' => (auth()->user()->can('corpline-quotes-edit') || (userHasProduct(quoteTypeCode::CORPLINE) && auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS))),
                'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && ! auth()->user()->hasRole(RolesEnum::PA),
                'isPA' => auth()->user()->hasRole(RolesEnum::PA),
            ],
            'typeCode' => quoteTypeCode::CORPLINE,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'companyTypes' => $companyType,
            'UBOsDetails' => $UBODetails,
            'UBORelations' => $UBORelations,
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,
            'emirates' => Emirate::where('is_active', 1)->select('id', 'text')->get(),
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::CorplineManager),
            'noteDocumentType' => $noteDocumentType,
            'quoteNotes' => $quoteNotes,
            'vatPercentage' => $vatPercentage,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'isNewPaymentStructure' => $isNewPaymentStructure,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'sendUpdateEnum' => $sendUpdateEnum,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'bookPolicyDetails' => $bookPolicyDetails,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocuments,
            'paymentGatewayEnum' => PaymentGatewayIdEnum::asArray(),
            'isFuncsEnabled' => ['tapIntegration' => isTapEnabled()],
            'leadTypeOptions' => $this->lookupService->getCorplineLeadTypes(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \\Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($id)
    {
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $dropdownSource = $this->businessQuoteService->dropdownSource($this->genericModel->properties, self::TYPE_ID);
        $subSources = app(LookupService::class)->getSubSource();

        return inertia('CorpLineQuote/Form', [
            'quote' => $record,
            'modelType' => $this->genericModel->modelType,
            'dropdownSource' => $dropdownSource,
            'subSources' => $subSources,
            'leadSourceParams' => [],
            'leadStatuses' => $dropdownSource['quote_status_id'],
            'genderOptions' => $this->crudService->getGenderOptions(),
            'lostReasons' => $this->lookupService->getLostReasons(),
            'isAdmin' => auth()->user()->isAdmin(),
            'permissions' => [
                'admin' => auth()->user()->hasAnyRole([RolesEnum::Admin]),
                'notProductionApproval' => ! auth()->user()->hasRole(RolesEnum::PA),
                'auditable' => auth()->user()->can(PermissionsEnum::Auditable),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBusinessQuoteRequest $request, $id)
    {
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;

        $this->crudService->updateModelByType('business', $request, $id);
        $this->businessQuoteService->updateBusinessQuote($request, $id);

        return redirect('/quotes/business/'.$id)->with('success', 'Business quote has been updated');
    }

    public function updateLeadType(Request $request, $uuid): RedirectResponse
    {
        $validValues = Lookup::where('key', LookupsEnum::CORPLINE_LEAD_TYPE->value)->pluck('code');

        $request->validate([
            'lead_type' => ['required', Rule::in($validValues)],
        ]);

        BusinessQuote::where('uuid', $uuid)->update(['lead_type' => $request->lead_type]);

        return back();
    }

    public function cardsView(Request $request)
    {

        $userTeams = auth()->user()->getUserTeams(auth()->id())->toArray();

        $newBusinessTeam = in_array(TeamNameEnum::CORPLINE_TEAM, $userTeams);
        $renewalsTeam = in_array(TeamNameEnum::CORPLINE_RENEWALS, $userTeams);

        $areBothTeamsPresent = $newBusinessTeam && $renewalsTeam;

        $isManagerOrDeputy = auth()->user()->hasAnyRole([RolesEnum::CorplineManager, RolesEnum::CorplineDeputyManager, RolesEnum::CorpLineRenewalManager]);

        if (($request->is_renewal === null && $areBothTeamsPresent) || ($request->is_renewal === null && $isManagerOrDeputy)) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        } elseif ($request->is_renewal === null && $newBusinessTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::noText]);
        } elseif ($request->is_renewal === null && $renewalsTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        }

        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::NewLead, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::ProposalFormRequested, 'title' => quoteStatusCode::PROPOSAL_FORM_REQUESTED, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::ProposalFormRequested, $request)],
            ['id' => QuoteStatusEnum::ProposalFormReceived, 'title' => quoteStatusCode::PROPOSAL_FORM_RECEIVED, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::ProposalFormReceived, $request)],
            ['id' => QuoteStatusEnum::PendingRenewalInformation, 'title' => quoteStatusCode::PENDING_RENEWAL_INFORMATION, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::PendingRenewalInformation, $request)],
            ['id' => QuoteStatusEnum::AdditionalInformationRequested, 'title' => quoteStatusCode::ADDITIONAL_INFORMATION_REQUESTED, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::AdditionalInformationRequested, $request)],
            ['id' => QuoteStatusEnum::QuoteRequested, 'title' => quoteStatusCode::QUOTE_REQUESTED, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::QuoteRequested, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::PaymentLinkSentToCustomer, 'title' => quoteStatusCode::PAYMENT_LINK_SENT_TO_CUSTOMER, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::PaymentLinkSentToCustomer, $request)],
            ['id' => QuoteStatusEnum::PaymentInitiated, 'title' => quoteStatusCode::PaymentInitiated, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::PaymentInitiated, $request)],
            ['id' => QuoteStatusEnum::FinalizingTerms, 'title' => quoteStatusCode::FINALIZING_TERMS, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::FinalizingTerms, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::BUSINESS->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $newBusiness = [
            QuoteStatusEnum::NewLead => 0,
            QuoteStatusEnum::ProposalFormRequested => 1,
            QuoteStatusEnum::ProposalFormReceived => 2,
            QuoteStatusEnum::AdditionalInformationRequested => 3,
            QuoteStatusEnum::QuoteRequested => 4,
            QuoteStatusEnum::Quoted => 5,
            QuoteStatusEnum::PaymentLinkSentToCustomer => 6,
            QuoteStatusEnum::PaymentInitiated => 7,
            QuoteStatusEnum::FinalizingTerms => 8,
            QuoteStatusEnum::PolicyIssued => 9,
        ];

        $renewals = [
            QuoteStatusEnum::Allocated => 0,
            QuoteStatusEnum::FollowedUp => 1,
            QuoteStatusEnum::PendingRenewalInformation => 2,
            QuoteStatusEnum::QuoteRequested => 3,
            QuoteStatusEnum::Quoted => 4,
            QuoteStatusEnum::PaymentLinkSentToCustomer => 5,
            QuoteStatusEnum::PaymentInitiated => 6,
            QuoteStatusEnum::FinalizingTerms => 7,
            QuoteStatusEnum::PolicyIssued => 8,
        ];

        if ($areBothTeamsPresent || $isManagerOrDeputy) {
            if ($request->is_renewal === quoteTypeCode::yesText) {
                $renewalKeys = array_keys($renewals);
                $quotes = array_filter($quotes, function ($quote) use ($renewalKeys) {
                    return in_array($quote['id'], $renewalKeys);
                });

                // Sort filtered quotes based on the renewals array order
                usort($quotes, function ($a, $b) use ($renewals) {
                    return $renewals[$a['id']] <=> $renewals[$b['id']];
                });
            }
            if ($request->is_renewal === quoteTypeCode::noText) {
                $newBusinessKeys = array_keys($newBusiness);
                $quotes = array_filter($quotes, function ($quote) use ($newBusinessKeys) {
                    return in_array($quote['id'], $newBusinessKeys);
                });

                // Sort filtered quotes based on the newBusiness array order
                usort($quotes, function ($a, $b) use ($newBusiness) {
                    return $newBusiness[$a['id']] <=> $newBusiness[$b['id']];
                });
            }

        } elseif ($newBusinessTeam) {
            $newBusinessKeys = array_keys($newBusiness);
            $quotes = array_filter($quotes, function ($quote) use ($newBusinessKeys) {
                return in_array($quote['id'], $newBusinessKeys);
            });

            // Sort filtered quotes based on the newBusiness array order
            usort($quotes, function ($a, $b) use ($newBusiness) {
                return $newBusiness[$a['id']] <=> $newBusiness[$b['id']];
            });
        } elseif ($renewalsTeam) {
            $renewalKeys = array_keys($renewals);
            $quotes = array_filter($quotes, function ($quote) use ($renewalKeys) {
                return in_array($quote['id'], $renewalKeys);
            });

            // Sort filtered quotes based on the renewals array order
            usort($quotes, function ($a, $b) use ($renewals) {
                return $renewals[$a['id']] <=> $renewals[$b['id']];
            });
        } elseif (array_intersect([TeamNameEnum::CORPLINE_TEAM], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::Allocated,
                QuoteStatusEnum::FollowedUp,
                QuoteStatusEnum::PendingRenewalInformation,
            ])->values()->toArray();
        } elseif (array_intersect([TeamNameEnum::CORPLINE_RENEWALS], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::NewLead,
                QuoteStatusEnum::ProposalFormRequested,
                QuoteStatusEnum::ProposalFormReceived,
                QuoteStatusEnum::AdditionalInformationRequested,
            ])->values()->toArray();
        }

        $advisors = app(CRUDService::class)->getAdvisorsByModelType(quoteTypeCode::CORPLINE);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Corpline);
        $insuranceTypeOptions = app(DropdownSourceService::class)->getDropdownSource('business_type_of_insurance_id', QuoteTypeId::Corpline);

        $totalLeads = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        foreach ($quotes as $item) {
            $totalLeads += $item['data']['total_leads'];
        }

        return inertia('CorpLineQuote/Cards', [
            'quotes' => $quotes,
            'renewalBatches' => $renewalBatches,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'teams' => $userTeams,
            'insuranceTypeOptions' => $insuranceTypeOptions,
            'quoteTypeId' => QuoteTypes::BUSINESS->id(),
            'quoteType' => QuoteTypes::BUSINESS->value,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $totalLeads : BusinessQuoteRepository::getData(quoteTypeCode::CORPLINE, true, true),
            'areBothTeamsPresent' => $areBothTeamsPresent || $isManagerOrDeputy ? true : false,
            'is_renewal' => ($areBothTeamsPresent || $isManagerOrDeputy ? 'Yes' : $renewalsTeam) ? 'Yes' : ($newBusinessTeam ? 'No' : null),
        ]);
    }
}
