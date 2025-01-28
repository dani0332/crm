<?php

namespace App\Http\Controllers;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\CarPlanAddonsCode;
use App\Enums\CarPlanExclusionsCode;
use App\Enums\CarPlanFeaturesCode;
use App\Enums\CarPlanType;
use App\Enums\CarTeamType;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthTeamType;
use App\Enums\HomePossessionType;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\PuaEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TiersEnum;
use App\Enums\TravelQuoteEnum;
use App\Events\LeadsCount;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Http\Requests\UpdatePolicyDetailRequest;
use App\Jobs\CarRenewalEmailJob;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Jobs\SyncSIBContactJob;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Models\Emirate;
use App\Models\GenericModel;
use App\Models\HealthPlanType;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Models\PolicyIssuanceStatus;
use App\Models\QuoteDocument;
use App\Models\Tier;
use App\Models\User;
use App\Repositories\AuditRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\HealthQuoteRepository;
use App\Repositories\HomeQuoteRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuoteNoteRepository;
use App\Repositories\RenewalBatchRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Repositories\UserRepository;
use App\Services\ActivitiesService;
use App\Services\AllocationService;
use App\Services\ApplicationStorageService;
use App\Services\BusinessQuoteService;
use App\Services\CarQuoteService;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\CustomerAddressService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\EmailDataService;
use App\Services\EmailServices\CarEmailService;
use App\Services\EmailStatusService;
use App\Services\HealthQuoteService;
use App\Services\HomeQuoteService;
use App\Services\LeadAllocationService;
use App\Services\LifeQuoteService;
use App\Services\LookupService;
use App\Services\MACRMService;
use App\Services\NotesForCustomerService;
use App\Services\NotificationService;
use App\Services\QuoteDocumentService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\SendEmailCustomerService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Services\TeamService;
use App\Services\TierService;
use App\Services\TravelQuoteService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class CRUDController extends Controller
{
    protected $genericModel;
    protected $healthQuoteService;
    protected $teamsService;
    protected $dropdownSourceService;
    protected $carQuoteService;
    protected $crudService;
    protected $travelQuoteService;
    protected $lifeQuoteService;
    protected $homeQuoteService;
    protected $businessQuoteService;
    protected $userService;
    protected $activityService;
    protected $emailStatusService;
    protected $applicationStorageService;
    protected $leadAllocationService;
    protected $lookupService;
    protected $notesForCustomerService;
    protected $customerService;
    protected $sendEmailCustomerService;
    protected $quoteDocumentService;
    protected $emailDataService;
    protected $allocationService;

    use GenericQueriesAllLobs, TeamHierarchyTrait;

    public function __construct(
        HealthQuoteService $healthService,
        TeamService $teamsService,
        CRUDService $crudService,
        DropdownSourceService $dropdownSourceService,
        CarQuoteService $carQuoteService,
        TravelQuoteService $travelQuoteService,
        LifeQuoteService $lifeQuoteService,
        HomeQuoteService $homeQuoteService,
        BusinessQuoteService $businessQuoteService,
        UserService $userService,
        Request $request,
        ActivitiesService $activityService,
        EmailStatusService $emailStatusService,
        ApplicationStorageService $applicationStorageService,
        LeadAllocationService $leadAllocationService,
        LookupService $lookupService,
        NotesForCustomerService $notesForCustomerService,
        CustomerService $customerService,
        SendEmailCustomerService $sendEmailCustomerService,
        QuoteDocumentService $quoteDocumentService,
        EmailDataService $emailDataService,
        AllocationService $allocationService
    ) {
        $this->genericModel = new GenericModel;
        $this->healthQuoteService = $healthService;
        $this->teamsService = $teamsService;
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->carQuoteService = $carQuoteService;
        $this->travelQuoteService = $travelQuoteService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->homeQuoteService = $homeQuoteService;
        $this->businessQuoteService = $businessQuoteService;
        $this->activityService = $activityService;
        $this->userService = $userService;
        $this->emailStatusService = $emailStatusService;
        $this->applicationStorageService = $applicationStorageService;
        $this->leadAllocationService = $leadAllocationService;
        $this->lookupService = $lookupService;
        $this->notesForCustomerService = $notesForCustomerService;
        $this->customerService = $customerService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->quoteDocumentService = $quoteDocumentService;
        $this->emailDataService = $emailDataService;
        $this->allocationService = $allocationService;
        $this->setModelType($request);
        $this->fillModelByModelType(ucwords($this->genericModel->modelType), $request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $renewalAdvisors = $upcomingBatch = [];
        $isNewBusinessUser = false;
        $isManualAllocationAllowed = false;
        $showDeadlineAlert = false;

        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();

        if (strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Car)) {
            $upcomingBatch = RenewalBatchRepository::getUpcomingBatch(QuoteStatusEnum::Uncontactable);

            if (
                isset($upcomingBatch->deadline->deadline_date) &&
                auth()->user()->hasAnyRole([RolesEnum::CarAdvisor]) &&
                UserRepository::isUserMemberOfTeam(auth()->user()->id, [CarTeamType::BDM, CarTeamType::SBDM, CarTeamType::RENEWALS])
            ) {
                $showDeadlineAlert = true;
            }

            $isManualAllocationAllowed = Auth::user()->isAdmin() || Auth::user()->hasRole(RolesEnum::LeadPool) ? true : false;
        } else {
            $userRoles = Auth::user()->usersroles()->get();
            $isManager = false;
            foreach ($userRoles as $userRole) {
                if (! str_contains(strtolower($userRole->name), 'deputy') && str_contains(strtolower($userRole->name), 'manager')) {
                    $isManager = true;
                }
            }
            $isManualAllocationAllowed = Auth::user()->isAdmin() ? true : $isManager;
        }
        $isCarLeadAllocationOn = $this->applicationStorageService->getValueByKey('CAR_LEAD_ALLOCATION_MASTER_SWITCH');
        $tiers = Tier::where('is_active', 1)->get();
        // Checking if the loggedIn user is Renewal User
        $isRenewalUser = Auth::user()->isRenewalUser();
        if ($isRenewalUser && strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Car)) {
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } elseif (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            $isRenewalUser = true;
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } elseif (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            $isNewBusinessUser = true;
            $renewalAdvisors = $this->crudService->getNewBusinessAdvisorsByModelType($this->genericModel->modelType);
        }
        // Getting the data for grid based on the model type
        $gridData = $this->crudService->getGridData($this->genericModel, $request);
        // Getting the data for the advisor dropdown based on the model type
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        // Get user teams
        $teams = $this->crudService->getUserTeams(Auth::user()->id);
        // Checking if the loggedIn user has Manager or Deputy Role
        $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
        $isLeadPool = Auth::user()->isLeadPool();
        $quoteTypeId = $this->activityService->getQuoteTypeId(strtolower($this->genericModel->modelType));
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();
        $dropdownSource = $customTitles = [];
        foreach ($this->genericModel->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                // Getting custom title for each property where title is mentioned in the property meta data
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'select')) {
                // Getting the dropdown source for each property where select is mentioned in the property meta data
                $dropdownValue = $this->dropdownSourceService->getDropdownSource($property, $quoteTypeId);
                $dropdownSource[$property] = $dropdownValue;
            }
        }
        $model = $this->genericModel;

        // inertia rendering for health quote
        // PD Revert
        // $count = $gridData->count();
        $count = 0;
        $hasOtherFilters = count(array_diff_key($request->all(), ['page' => ''])) > 0;

        if ($this->genericModel->modelType == quoteTypeCode::Health) {
            $gridData = $gridData->simplePaginate(10)->withQueryString();

            $quote_status = $dropdownSource['quote_status_id'];
            $quote_status = collect($quote_status)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::Lost;
            })->values();

            $todaysAllocationData = $this->allocationService->getHealthTodaysCount(auth()->user()->id);
            $userMaxCap = $todaysAllocationData['max_capacity'];
            $todayAutoCount = $todaysAllocationData['auto_assignment_count'];
            $todayManualCount = $todaysAllocationData['manual_assignment_count'];
            $yesterdayAllocationData = $this->allocationService->getHealthYesterdayCounts(auth()->user()->id);
            $yesterdayAutoCount = $yesterdayAllocationData['auto_assignment_count'];
            $yesterdayManualCount = $yesterdayAllocationData['manual_assignment_count'];

            return inertia('HealthQuote/Index', [
                'quotes' => $gridData,
                'renewalBatches' => $renewalBatches,
                'leadStatuses' => $quote_status,
                'advisors' => $advisors,
                'teams' => $teams,
                'userMaxCap' => $userMaxCap,
                'todayAutoCount' => $todayAutoCount,
                'todayManualCount' => $todayManualCount,
                'yesterdayAutoCount' => $yesterdayAutoCount,
                'yesterdayManualCount' => $yesterdayManualCount,
                'quoteSegments' => QuoteSegmentEnum::withLabels(QuoteTypeId::Health),
                'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $count : HealthQuoteRepository::getData(true, true),
                'authorizedDays' => intval($authorizedDays->value),
                'assignmentTypes' => AssignmentTypeEnum::withLabels(),
            ]);
        }

        // inertia rendering for home quote
        if ($this->genericModel->modelType == quoteTypeCode::Home) {
            $gridData = $gridData->simplePaginate(10)->withQueryString();

            $quote_status = $dropdownSource['quote_status_id'];
            $quote_status = collect($quote_status)->filter(function ($value) {
                return $value['id'] != QuoteStatusEnum::Lost;
            })->values();

            return inertia('HomeQuote/Index', [
                'quotes' => $gridData,
                'renewalBatches' => $renewalBatches,
                'leadStatuses' => $quote_status,
                'advisors' => $advisors,
                'isManualAllocationAllowed' => $isManualAllocationAllowed,
                'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $count : HomeQuoteRepository::getData(true, true),
                'authorizedDays' => intval($authorizedDays->value),
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Car) {
            $gridData = $gridData->simplePaginate(10)->withQueryString();

            $userMaxCap = 0;
            $todayAutoCount = 0;
            $todayManualCount = 0;
            $yesterdayAutoCount = 0;
            $yesterdayManualCount = 0;

            $todaysAllocationData = $this->allocationService->getTodayCounts(auth()->user()->id);
            $userMaxCap = $todaysAllocationData['max_capacity'];
            $todayAutoCount = $todaysAllocationData['auto_assignment_count'];
            $todayManualCount = $todaysAllocationData['manual_assignment_count'];
            $yesterdayAllocationData = $this->allocationService->getYesterdayCounts(auth()->user()->id);
            $yesterdayAutoCount = $yesterdayAllocationData['auto_assignment_count'];
            $yesterdayManualCount = $yesterdayAllocationData['manual_assignment_count'];

            $dateFormat = config('constants.DATE_FORMAT_ONLY');
            $createdAtStart = Carbon::parse(now())->startOfDay()->format($dateFormat);
            $createdAtEnd = Carbon::parse(now())->endOfDay()->format($dateFormat);
            $genericRequestEnum = GenericRequestEnum::asArray();
            $isBetaUser = auth()->user()->hasRole(RolesEnum::BetaUser);
            $productTeam = $this->getProductByName(quoteTypeCode::Car);
            $teams = $this->getTeamsByProductId($productTeam->id);

            return inertia('PersonalQuote/Car/LeadList', [
                'quotes' => $gridData,
                'advisors' => $advisors,
                'createdAtStart' => $createdAtStart,
                'createdAtEnd' => $createdAtEnd,
                'dropdownSource' => $dropdownSource,
                'isManualAllocationAllowed' => $isManualAllocationAllowed,
                'userMaxCap' => $userMaxCap,
                'todayAutoCount' => $todayAutoCount,
                'todayManualCount' => $todayManualCount,
                'yesterdayAutoCount' => $yesterdayAutoCount,
                'yesterdayManualCount' => $yesterdayManualCount,
                'genericRequestEnum' => $genericRequestEnum,
                'isBetaUser' => $isBetaUser,
                'teams' => $teams,
                'authorizedDays' => intval($authorizedDays->value),
                'assignmentTypes' => AssignmentTypeEnum::withLabels(),
            ]);
        }

        if ($request->ajax()) {
            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
        }

        return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy', 'isRenewalUser', 'renewalAdvisors', 'isNewBusinessUser', 'isLeadPool', 'isCarLeadAllocationOn', 'tiers', 'isManualAllocationAllowed', 'userMaxCap', 'todayAssignmentCount', 'upcomingBatch', 'showDeadlineAlert'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $isRenewalUser = Auth::user()->isRenewalUser();
        if ($isRenewalUser && strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Car)) {
            $renewalAdvisors = $this->crudService->fillRenewalData($this->genericModel);
        } elseif (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            $isRenewalUser = true;
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } elseif (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            $isNewBusinessUser = true;
            $renewalAdvisors = $this->crudService->getNewBusinessAdvisorsByModelType($this->genericModel->modelType);
        }
        $customTitles = $dropdownSource = [];
        foreach ($this->genericModel->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'select')) {
                $data = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $data;
            }
        }
        $model = $this->genericModel;

        if ($this->genericModel->modelType == quoteTypeCode::Health) {
            return inertia('HealthQuote/Form', [
                'dropdownSource' => $dropdownSource,
                'model' => json_encode($model->properties),
                'genderOptions' => $this->crudService->getGenderOptions(),
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Car) {
            $dropdownSource['car_make_id'] = $this->getCarMakeDropdown();

            return inertia('PersonalQuote/Car/Form', [
                'dropdownSource' => $dropdownSource,
                'model' => json_encode($model->properties),
                'genderOptions' => $this->crudService->getGenderOptions(),
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Home) {
            return inertia('HomeQuote/Form', [
                'dropdownSource' => $dropdownSource,
                'model' => json_encode($model->properties),
                'homePossessionTypeEnum' => HomePossessionType::asArray(),
            ]);
        }

        return view('shared.add', compact('model', 'dropdownSource', 'customTitles', 'isRenewalUser'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $modelPropertiesList = json_decode($request->get('model'), true);
        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        $modelType = json_decode($request->get('modelType'), true);
        $validateArray = $modelDetails = [];

        if ($modelType !== quoteTypeCode::Health && $modelType !== quoteTypeCode::Home && $modelType !== quoteTypeCode::Car) {
            foreach ($modelPropertiesList as $property => $value) {
                if (strpos($value, 'required') && $property != 'id' && ! strpos($modelSkipPropertiesList['create'], $property)) {
                    $validateArray[$property] = 'required';
                }
            }
        }
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;

        if ($modelType == quoteTypeCode::Health || $modelType == quoteTypeCode::Car || $modelType == quoteTypeCode::Travel || $modelType == quoteTypeCode::Home) {
            $validateArray = [];
            $modelDetails[quoteTypeCode::Home]['totalLeadsCount'] = HomeQuoteRepository::getData(true, true);
            $modelDetails[quoteTypeCode::Health]['totalLeadsCount'] = HealthQuoteRepository::getData(true, true);

            if ($request->has('first_name')) {
                $this->validate($request, [
                    'first_name' => 'required|between:1,20',
                ]);
            }
            if ($request->has('last_name')) {
                $this->validate($request, [
                    'last_name' => 'required|between:1,50',
                ]);
            }
        }
        // new ui enabled
        if ($modelType == quoteTypeCode::Home) {
            $validateArray = [];
            if ($request->has('ilivein_accommodation_type_id')) {
                $this->validate($request, [
                    'ilivein_accommodation_type_id' => 'required|exists:home_accommodation_type,id',
                ]);
            }
            if ($request->has('iam_possesion_type_id')) {
                $this->validate($request, [
                    'iam_possesion_type_id' => 'required|exists:home_possession_type,id',
                ]);
            }
            if ($request->has('address')) {
                $this->validate($request, [
                    'address' => 'required|max:2000',
                ]);
            }
        } elseif ($modelType == quoteTypeCode::Home) {
            $validateArray = $this->homeQuoteService->getValidationArray($modelPropertiesList, $request, $modelSkipPropertiesList['create']);
        } elseif ($modelType == quoteTypeCode::Car) {
            $validateArray = $this->carQuoteService->getValidationArray($request);
        }

        if ($request->has('email')) {
            $this->validate($request, [
                'email' => 'required|email:rfc,dns|max:150',
            ]);
        }

        if ($request->has('type_of_pet1')) {
            $this->validate($request, [
                'type_of_pet1' => 'required|max:3',
            ]);
        }
        if ($request->has('mobile_no')) {
            $this->validate($request, [
                'mobile_no' => 'required|regex:/(0)[0-9]/|not_regex:/[a-z]/|min:7|max:20',
            ]);
        }

        $this->validate($request, $validateArray);
        app(CustomerAddressService::class)->validateAddress($request);
        $record = $this->crudService->saveModelByType($modelType, $request);

        if ($record) {
            $customerId = $this->customerService->getCustomerIdByEmail($request->email);

            if ($request->has('addressObj') && ! empty(array_filter((array) $request->input('addressObj')))) {
                app(CustomerAddressService::class)->createOrUpdateCustomerAddress($request->input('addressObj'), $customerId, $record->quoteUID);
            }
        }

        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return Redirect::back()->with('message', $record->message)->withInput();
        } else {
            if (in_array($modelType, [quoteTypeCode::Health, quoteTypeCode::Home])) {
                event(new LeadsCount($modelDetails[$modelType]['totalLeadsCount']));
            }

            if (! isset($record->quoteUID)) {
                return redirect('/quotes/'.strtolower($modelType))->with('success', ((str_contains(strtolower($modelType), 'team') ? 'Team' : (str_contains(strtolower($modelType), 'leadstatus') ? 'Lead Status' : $modelType))).' has been stored');
            } else {
                return redirect('/quotes/'.strtolower($modelType).'/'.$record->quoteUID)->with('success', ((str_contains(strtolower($modelType), 'team') ? 'Team' : (str_contains(strtolower($modelType), 'leadstatus') ? 'Lead Status' : 'Lead'))).' has been created');
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * @return \Inertia\Response
     */
    public function show($id, Request $request)
    {
        if (strrpos(request()->getRequestUri(), '/') === strlen(request()->getRequestUri()) - 1) {
            // Fix for trailing slash when loading plans through jQuery
            return redirect(request()->url());
        }
        $quoteType = strtolower($this->genericModel->modelType);
        $quoteTypeId = $this->activityService->getQuoteTypeId($quoteType);
        $paymentTooltipEnum = PaymentTooltip::asArray();
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        abort_if(! $record, 404);

        /* Start - Temporarily adding for correcting historic data */
        (new PaymentRepository)->updatePriceVatApplicableAndVat($record, $this->genericModel->modelType);
        /* End - Temporarily adding for correcting historic data */

        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails($this->genericModel->modelType, $record);

        $autoAllocationDisabled = $this->lookupService->getApplicationStorageValue('LEAD_ALLOCATION_JOB_SWITCH');
        if (strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Health) && Auth::user()->isHealthWCUAdvisor() && $record->wcu_id != Auth::user()->id && $autoAllocationDisabled == '1') {
            abort(403, 'Unauthorized action.');
        }
        $paymentEntityModel = $this->{strtolower($this->genericModel->modelType).'QuoteService'}->getEntityPlain($record->id);
        $payments = $paymentEntityModel->payments;

        $mainPayment = $paymentEntityModel->payments()->where('code', '=', $paymentEntityModel->code)->first();
        $paymentLink = config('constants.PAYMENT_REDIRECT_LINK');

        $paymentMethods = $this->lookupService->getPaymentMethods();
        $mappedQuoteTypeId = ($quoteTypeId == QuoteTypeId::Business) ? QuoteTypeId::Corpline : $quoteTypeId;
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping($mappedQuoteTypeId);
        $isRenewalUser = false;
        $isNewBusinessUser = false;
        $model = $this->genericModel;
        $model_name = $this->genericModel->modelType.'Quote';

        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = $this->crudService->hasAtleastOneStatusPolicyIssued($record);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = $this->lookupService->getSendUpdateOptions($quoteTypeId);
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($record->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $policyIssuanceStatus = PolicyIssuanceStatus::active()->get();

        $customTitles = $customTableList = [];
        if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            $isRenewalUser = true;
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } elseif (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            $isNewBusinessUser = true;
            $renewalAdvisors = $this->crudService->getNewBusinessAdvisorsByModelType($this->genericModel->modelType);
        }
        $leadStatuses = $this->dropdownSourceService->getDropdownSource('quote_status_id', $quoteTypeId);
        $leadStatuses = collect($leadStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $leadStatuses = app(CentralService::class)->lockTransactionStatus($record, $quoteTypeId, $leadStatuses);

        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $leadStatuses = collect($leadStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }

        $lostReasons = $this->lookupService->getLostReasons();
        $selectedLostReasonId = '';
        if (strtolower($this->genericModel->modelType) != 'teams' && strtolower($this->genericModel->modelType) != 'leadstatus') {
            $selectedLostReasonId = $this->crudService->getSelectedLostReason($this->genericModel->modelType, $record->id);
        }
        $advisors = [];
        if (
            ! (auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarAdvisor])) &&
            strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Health) && ($record->health_team_type == HealthTeamType::EBP ||
                $record->health_team_type == HealthTeamType::RM_NB || $record->health_team_type == HealthTeamType::RM_SPEED)
        ) {
            $advisors = $this->crudService->getEBPAndRMAdvisors();
        } elseif (strtolower($this->genericModel->modelType) == 'business') {
            $advisors = $this->crudService->getRMAndBusinessAdvisors();
        } else {
            $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        }
        foreach ($model->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'customTable')) {
                $customTableList[$property] = $this->dropdownSourceService->getOnlySelectedItemName($property, $id);
            }
        }
        $quoteTypes = 'Health,Car,Travel,Life,Home,Business,Pet';
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($model->modelType, $record->code);
        $activitiesData = $this->activityService->getActivityByLeadId($record->id, strtolower($model->modelType));
        $activities = [];
        foreach ($activitiesData as $activity) {
            $updatedActivity = [
                'id' => $activity->id,
                'uuid' => $activity->uuid,
                'title' => $activity->title,
                'description' => $activity->description,
                'quote_request_id' => $activity->quote_request_id,
                'quote_type_id' => $activity->quote_type_id,
                'quote_uuid' => $activity->quote_uuid,
                'client_name' => $activity->client_name,
                'due_date' => $activity->due_date,
                'assignee' => User::where('id', $activity->assignee_id)->first()->name,
                'assignee_id' => $activity->assignee_id,
                'status' => $activity->status,
                'is_cold' => $activity->is_cold,
                'quote_status_id' => $activity->quote_status_id,
                'quote_status' => $activity?->quoteStatus,
                'user_id' => $activity?->user_id,
            ];
            array_push($activities, $updatedActivity);
        }
        $audits = [];
        $emailStatuses = $this->emailStatusService->getEmailStatus($quoteTypeId, $record->id);
        $notesForCustomers = $this->notesForCustomerService->getNotesForCustomer($quoteTypeId, $record->id);
        $advisor = isset($record->advisor_id) ? $this->userService->getUserById((int) $record->advisor_id) : null;
        $isQuoteDocumentEnabled = $this->quoteDocumentService->isEnabled($model->modelType);
        $quoteDocuments = $this->quoteDocumentService->getQuoteDocuments($model->modelType, $record->id);
        $displaySendPolicyButton = (bool) $this->quoteDocumentService->showSendPolicyButton($record, $quoteDocuments, $quoteTypeId);
        $customerAdditionalContacts = $this->customerService->getAdditionalContacts($record->customer_id, $record->mobile_no);
        $tiers = $this->lookupService->getTierR();

        $access = $this->carQuoteService->updatedAccessAgainstPaymentStatus($paymentEntityModel, $record);

        if (in_array($this->genericModel->modelType, [quoteTypeCode::Health, quoteTypeCode::Car])) {
            $clientInquiryLogs = $this->crudService->getInquiryLogs($this->genericModel->modelType, $record->uuid) ?? [];
        }

        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($record);

        $puaTypeEnum = PuaEnum::asArray();
        $isNewPaymentStructure = app(SplitPaymentService::class)->isNewPaymentStructure($payments);
        if ($this->genericModel->modelType == quoteTypeCode::Car) { // Car plans to display on detail view
            $quote = $record;
            $isCommercialVehicles = false;
            $carInsuranceProviders = [];
            $ecomCarInsuranceQuoteUrl = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL');
            $carQuotePlanAddons = $this->carQuoteService->getCarQuotePlanAddons($id);
            $vehicleTypes = $this->lookupService->getVehicleTypes();
            $trimList = $this->lookupService->getTrimListByCarModel($record->car_model_id);
            $yearsOfManufacture = $this->lookupService->getYearsOfManufacture();
            $carMakeText = $record->car_make_id_text ?? '';
            $carModelText = $record->car_model_id_text ?? '';

            $this->carQuoteService->addOrUpdateQuoteViewCount($record, QuoteTypeId::Car);
            $record->payment_status_id_text = app(SplitPaymentService::class)->mapQuotePaymentStatus($record->payment_status_id, $record->payment_status_id_text);

            foreach ($payments as $payment) {
                $payment->payment_status_text = $payment->paymentStatus->text;
                $payment->last_payment_status_created_at = $payment->paymentStatusLogs->last() != null ? $payment->paymentStatusLogs->last()->created_at : '';
                $payment->payment_method_name = $payment->paymentMethod->name;
                $payment->insurance_provider_id_text = $payment->insuranceProvider->text;
            }

            $lostRejectReasons = LookupRepository::where('key', LookupsEnum::CAR_LOST_REJECT_REASONS)->get();
            $lostApproveReasons = LookupRepository::where('key', LookupsEnum::CAR_LOST_APPROVE_REASONS)->get();

            if (isCarLostStatus($record->quote_status_id)) {
                $paymentEntityModel->load(['carLostQuoteLogs' => function ($q) {
                    $q->with(['advisor', 'quoteStatus', 'documents', 'actionBy'])->orderBy('id', 'desc');
                }, 'carLostQuoteLog']);
            }

            [$allowQuoteLogAction, $carLostChangeStatus, $leadStatuses] = $this->carQuoteService->checkCarLostPermissions($record, $paymentEntityModel, $leadStatuses);

            if (($record->source != LeadSourceEnum::RENEWAL_UPLOAD || auth()->user()->hasRole(RolesEnum::CarManager)) && $leadStatuses != null && ! is_array($leadStatuses)) {
                $leadStatuses = $leadStatuses->whereNotIn('id', [QuoteStatusEnum::CarSold, QuoteStatusEnum::EarlyRenewal])->all();
            }

            $daysAfterCapturedPayment = null;
            if (($capturedPaymentDate = PaymentStatusLog::where([
                'quote_type_id' => QuoteTypeId::Car,
                'quote_request_id' => $record->id,
                'current_payment_status_id' => PaymentStatusEnum::CAPTURED,
            ])->first())) {
                $daysAfterCapturedPayment = Carbon::now()->diffInDays(Carbon::parse($capturedPaymentDate->created_at));
            }

            $paymentEntityModel->load(['plan.insuranceProvider']);
            $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::CAR->id(), $record->id);

            if (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager])) {
                if (InsuranceProviderRepository::isCommercialVehicles($record)) {
                    $isCommercialVehicles = true;
                    $carInsuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Car);
                }
            }

            $websiteURL = config('constants.AFIA_WEBSITE_DOMAIN');
            $carPlanFeaturesCodeEnum = CarPlanFeaturesCode::asArray();
            $carPlanExclusionsCodeEnum = CarPlanExclusionsCode::asArray();
            $carPlanAddonsCodeEnum = CarPlanAddonsCode::asArray();
            $leadSourceEnum = LeadSourceEnum::asArray();
            $genericRequestEnum = GenericRequestEnum::asArray();
            $carPlanTypeEnum = CarPlanType::asArray();
            $docUploadURL = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$record->uuid.'/thankyou';
            @[$documentTypes, $paymentDocument] = $this->quoteDocumentService->getDocumentTypes(QuoteTypeId::Car);
            $quoteDocuments = array_values($quoteDocuments->toArray());
            $planURL = $ecomCarInsuranceQuoteUrl.$record->uuid;
            $storageUrl = storageUrl();
            $isPlanUpdateActive = $this->applicationStorageService->getIsActiveByKey('IMCRM_CAR_QUOTE_PLANS_EDIT_IS_DISABLED');
            $isPlanUpdateActive = $isPlanUpdateActive == ApplicationStorageEnums::INACTIVE;
            $insuranceProviders = $this->lookupService->getAllInsuranceProviders();
            $tierService = new TierService;
            $tiersExceptTierR = $tierService->getTiersExceptTierR();
            $isTierRAssigned = $tierService->isTierRAssigned($record->tier_id);
            $leadDocsStoragePath = createCdnUrl('');
            $kyoEndPoint = config('constants.KYO_END_POINT');
            $isBetaUser = auth()->user()->hasRole(RolesEnum::BetaUser);
            $UBOsDetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::CAR->name, CustomerTypeEnum::Entity);
            $UBORelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
            $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
            $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
            $membersDetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::CAR->name);
            $customerTypeEnum = CustomerTypeEnum::asArray();
            $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
            $nationalities = NationalityRepository::withActive()->get();
            $insuranceProvidersByQuoteType = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::CAR->id());
            $commercialRules = $this->leadAllocationService->isCommercialVehicles($record);
            $clientInquiryLogs = $this->crudService->getInquiryLogs($this->genericModel->modelType, $record->uuid) ?? [];
            $bookPolicyDetails = $this->bookPolicyPayload($record, $quoteType, $payments, $quoteDocuments);

            $customerAddressData = $this->customerService->getCustomerAddressData($record);
            $amlStatusName = AMLStatusCode::getName($record->aml_status);

            return inertia('PersonalQuote/Car/Show', compact([
                'record',
                'sendUpdateOptions',
                'sendUpdateLogs',
                'quote',
                'model',
                'customTitles',
                'customTableList',
                'leadSourceEnum',
                'isBetaUser',
                'sendUpdateEnum',
                'ecomCarInsuranceQuoteUrl',
                'carQuotePlanAddons',
                'vehicleTypes',
                'leadStatuses',
                'docUploadURL',
                'isPlanUpdateActive',
                'allowQuoteLogAction',
                'carLostChangeStatus',
                'lostReasons',
                'selectedLostReasonId',
                'model_name',
                'allowedDuplicateLOB',
                'audits',
                'websiteURL',
                'insuranceProviders',
                'leadDocsStoragePath',
                'activities',
                'advisors',
                'isRenewalUser',
                'isNewBusinessUser',
                'emailStatuses',
                'carPlanAddonsCodeEnum',
                'tiersExceptTierR',
                'isTierRAssigned',
                'yearsOfManufacture',
                'notesForCustomers',
                'quoteType',
                'quoteTypeId',
                'trimList',
                'autoAllocationDisabled',
                'embeddedProducts',
                'genericRequestEnum',
                'paymentEntityModel',
                'payments',
                'paymentMethods',
                'isQuoteDocumentEnabled',
                'quoteDocuments',
                'displaySendPolicyButton',
                'customerAdditionalContacts',
                'lostApproveReasons',
                'lostRejectReasons',
                'carMakeText',
                'carModelText',
                'advisor',
                'tiers',
                'daysAfterCapturedPayment',
                'access',
                'carPlanFeaturesCodeEnum',
                'carPlanExclusionsCodeEnum',
                'documentTypes',
                'planURL',
                'storageUrl',
                'kyoEndPoint',
                'carPlanTypeEnum',
                'UBORelations',
                'UBOsDetails',
                'emirates',
                'customerTypeEnum',
                'memberRelations',
                'membersDetails',
                'industryType',
                'nationalities',
                'paymentTooltipEnum',
                'isCommercialVehicles',
                'carInsuranceProviders',
                'isNewPaymentStructure',
                'hasPolicyIssuedStatus',
                'insuranceProvidersByQuoteType',
                'vatPercentage',
                'commercialRules',
                'clientInquiryLogs',
                'policyIssuanceStatus',
                'bookPolicyDetails',
                'linkedQuoteDetails',
                'puaTypeEnum',
                'lockLeadSectionsDetails',
                'paymentDocument',
                'customerAddressData',
                'amlStatusName',
            ]));
        }

        if ($this->genericModel->modelType == quoteTypeCode::Travel) { // Travel plans to display on detail view
            $ecomTravelInsuranceQuoteUrl = config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL');
            $membersDetail = $this->travelQuoteService->getMembersDetail($record->id);

            return view('shared.show', compact([
                'record',
                'model',
                'customTitles',
                'listQuotePlans',
                'customTableList',
                'leadStatuses',
                'lostReasons',
                'selectedLostReasonId',
                'membersDetail',
                'model_name',
                'allowedDuplicateLOB',
                'audits',
                'activities',
                'advisors',
                'isRenewalUser',
                'isNewBusinessUser',
                'ecomTravelInsuranceQuoteUrl',
                'quoteType',
                'autoAllocationDisabled',
                'paymentEntityModel',
                'payments',
                'mainPayment',
                'paymentMethods',
                'insuranceProviders',
                'emailStatuses',
                'isQuoteDocumentEnabled',
                'quoteDocuments',
                'displaySendPolicyButton',
                'customerAdditionalContacts',
                'quoteTypeId',
                'tiers',
                'access',
                'isNewPaymentStructure',
                'hasPolicyIssuedStatus',
            ]));
        }

        if ($this->genericModel->modelType == quoteTypeCode::Home) {
            $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
            $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
            $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $domainPath = config('constants.AFIA_WEBSITE_DOMAIN');
            $notProductionApproval = ! auth()->user()->hasRole(RolesEnum::PA);
            $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::HOME->id(), $record->id);
            $membersDetail = CustomerMembersRepository::getBy($record->id, QuoteTypes::HOME->name);
            $payments->load(['paymentStatus', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider']);
            $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
            $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Home);
            $uboDetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::HOME->name, CustomerTypeEnum::Entity);
            $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
            $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

            $payments->each(function ($payment) {
                $allow = $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED && ! auth()->user()->hasRole(RolesEnum::PA);
                $payment->copy_link_button = $allow && optional($payment->paymentMethod)->code == PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID;
                $payment->edit_button = $allow && $payment->payment_status_id != PaymentStatusEnum::PAID;
                $payment->approve_button = optional($payment->paymentMethod)->code != PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID && $payment->payment_status_id != PaymentStatusEnum::CAPTURED
                    && ! auth()->user()->hasRole(RolesEnum::PA);

                $payment->approved_button = $payment->payment_status_id == PaymentStatusEnum::PAID;
            });

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
            $filteredInsuranceProviders = [];
            if (! empty($insuranceProviders)) {
                $filteredInsuranceProviders = $insuranceProviders->map(function ($paymentMethod) {
                    return [
                        'value' => $paymentMethod->id,
                        'label' => $paymentMethod->text,
                    ];
                })->sortBy('label')->values();
            }

            @[$documentTypes, $paymentDocument] = $this->quoteDocumentService->getDocumentTypes(QuoteTypeId::Home);

            $quoteDocument = (new QuoteDocumentService)->getQuoteDocuments(QuoteTypes::HOME->value, $record->id);
            $bookPolicyDetails = $this->bookPolicyPayload($record, QuoteTypes::HOME->value, $payments, $quoteDocument);
            $noteDocumentType = DocumentType::where('code', DocumentTypeCode::OD)->first();
            $quoteNotes = QuoteNoteRepository::getBy($record->id, QuoteTypes::HOME->name);
            $amlStatusName = AMLStatusCode::getName($record->aml_status);

            return inertia('HomeQuote/Show', [
                'storageUrl' => storageUrl(),
                'quoteDocuments' => $quoteDocuments,
                'quote' => $record,
                'amlStatusName' => $amlStatusName,
                'record' => $record,
                'sendUpdateOptions' => $sendUpdateOptions,
                'sendUpdateLogs' => $sendUpdateLogs,
                'allowedDuplicateLOB' => $allowedDuplicateLOB,
                'leadStatuses' => array_values($leadStatuses->toArray()),
                'advisors' => $advisors,
                'cdnPath' => $cdnPath,
                'domainPath' => $domainPath,
                'activities' => $activities,
                'customerAdditionalContacts' => $customerAdditionalContacts,
                'lostReasons' => $lostReasons,
                'payments' => $payments,
                'quoteRequest' => $paymentEntityModel,
                'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
                'paymentMethods' => $filteredPaymentMethods,
                'insuranceProviders' => $filteredInsuranceProviders,
                'permissions' => [
                    'pa' => auth()->user()->hasRole(RolesEnum::PA),
                    'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                    'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                    'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && ! auth()->user()->hasRole(RolesEnum::PA),
                    'isPA' => auth()->user()->hasRole(RolesEnum::PA),
                    'isAdvisor' => auth()->user()->hasRole(RolesEnum::EBPAdvisor) || auth()->user()->hasRole(RolesEnum::HealthAdvisor) || auth()->user()->hasRole(RolesEnum::RMAdvisor),
                    'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                ],
                'modelType' => $quoteType,
                'quoteTypeId' => $quoteTypeId,
                'notProductionApproval' => $notProductionApproval,
                'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
                'quoteRequest' => $paymentEntityModel,
                'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::HomeManager),
                'embeddedProducts' => $embeddedProducts,
                'customerTypeEnum' => CustomerTypeEnum::asArray(),
                'membersDetails' => $membersDetail,
                'memberRelations' => $memberRelations,
                'nationalities' => $nationalities,
                'industryType' => $industryType,
                'UBOsDetails' => $uboDetails,
                'UBORelations' => $uboRelations,
                'emirates' => $emirates,
                'quoteType' => QuoteTypes::HOME,
                'paymentTooltipEnum' => PaymentTooltip::asArray(),
                'documentTypes' => $documentTypes,
                'bookPolicyDetails' => $bookPolicyDetails,
                'noteDocumentType' => $noteDocumentType,
                'quoteNotes' => $quoteNotes,
                'vatPercentage' => $vatPercentage,
                'isNewPaymentStructure' => $isNewPaymentStructure,
                'sendUpdateEnum' => $sendUpdateEnum,
                'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
                'linkedQuoteDetails' => $linkedQuoteDetails,
                'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
                'paymentDocument' => $paymentDocument,
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Health) { // Health plans to display on detail view
            $this->carQuoteService->addOrUpdateQuoteViewCount($record, QuoteTypeId::Health);
            $coPayment = $this->healthQuoteService->getCoPayment($id);
            $uboDetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::HEALTH->name, CustomerTypeEnum::Entity);
            $membersDetail = CustomerMembersRepository::getBy($record->id, QuoteTypes::HEALTH->name);
            $memberCategories = $this->lookupService->getMemberCategories();
            $salaryBands = $this->lookupService->getSalaryBands();
            $ecomDetails = $this->healthQuoteService->getEcomDetails($record);
            $ecomHealthInsuranceQuoteUrl = config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL');
            $leadStatuses = $this->healthQuoteService->statusesToDisplay($leadStatuses, $record);
            $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
            $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
            $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
            $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
            $noteDocumentType = DocumentType::where('code', DocumentTypeCode::OD)->first();
            $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();

            @[$documentTypes, $paymentDocument] = $this->quoteDocumentService->getDocumentTypes(QuoteTypeId::Health);
            $quoteDocuments = $quoteDocuments->map(function ($quoteDocument) {
                $quoteDocument->created_by_name = isset($quoteDocument->createdBy->name) ? $quoteDocument->createdBy->name : null;

                return $quoteDocument;
            });

            $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $domainPath = config('constants.AFIA_WEBSITE_DOMAIN');

            $notProductionApproval = ! auth()->user()->hasRole(RolesEnum::PA);
            $payments->load(['paymentStatus', 'healthPlan.insuranceProvider', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider']);
            $paymentEntityModel->load(['plan.insuranceProvider']);

            $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Health);

            $hashCollapsibleStatuses = $this->crudService->hashCollapsibleStatuses($quoteTypeId, $record->id);

            $payments->each(function ($payment) {
                $allow = $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED && ! auth()->user()->hasRole(RolesEnum::PA);
                $payment->copy_link_button = $allow && optional($payment->paymentMethod)->code == PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID;
                $payment->edit_button = $allow && $payment->payment_status_id != PaymentStatusEnum::PAID;
                $payment->approve_button = optional($payment->paymentMethod)->code != PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID && $payment->payment_status_id != PaymentStatusEnum::CAPTURED
                    && ! auth()->user()->hasRole(RolesEnum::PA);

                $payment->approved_button = $payment->payment_status_id == PaymentStatusEnum::PAID;
            });
            $isNewPaymentStructure = app(SplitPaymentService::class)->isNewPaymentStructure($payments);
            if ($isNewPaymentStructure) {
                $paymentMethods = $this->lookupService->getPaymentMethods();
            } else {
                $paymentMethods = $paymentMethods->filter(function ($paymentMethod) {
                    return $paymentMethod->code == PaymentMethodsEnum::CreditCard;
                })->map(function ($paymentMethod) {
                    return [
                        'value' => $paymentMethod->code,
                        'label' => $paymentMethod->name,
                    ];
                })->values();
            }
            if (! empty($insuranceProviders)) {
                $insuranceProviders = $insuranceProviders?->map(function ($paymentMethod) {
                    return [
                        'value' => $paymentMethod->id,
                        'label' => $paymentMethod->text,
                    ];
                })->sortBy('label')->values();
            }

            $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::HEALTH->id(), $record->id);
            $healthPlanTypes = HealthPlanType::where('is_active', 1)->select('id', 'text')->get();
            $bookPolicyDetails = $this->bookPolicyPayload($record, $quoteType, $payments, $quoteDocuments);
            $quoteNotes = QuoteNoteRepository::getBy($record->id, QuoteTypes::HEALTH->name);
            $teams = $this->crudService->getUserTeams(Auth::user()->id);

            $record->payment_status_text = app(SplitPaymentService::class)->mapQuotePaymentStatus($record->payment_status_id, $record->payment_status_text);
            $amlStatusName = AMLStatusCode::getName($record->aml_status);

            return inertia('HealthQuote/Show', [
                'paymentLink' => $paymentLink,
                'emailStatuses' => $emailStatuses,
                'quote' => $record,
                'amlStatusName' => $amlStatusName,
                'sendUpdateOptions' => $sendUpdateOptions,
                'sendUpdateLogs' => $sendUpdateLogs,
                'genderOptions' => $this->crudService->getGenderOptions(),
                'allowedDuplicateLOB' => $allowedDuplicateLOB,
                'leadStatuses' => array_values($leadStatuses->toArray()),
                'ecomDetails' => $ecomDetails,
                'coPayment' => $coPayment,
                'membersDetail' => $membersDetail,
                'memberCategories' => $memberCategories,
                'memberRelations' => $memberRelations,
                'salaryBands' => $salaryBands,
                'ecomHealthInsuranceQuoteUrl' => $ecomHealthInsuranceQuoteUrl,
                'nationalities' => $nationalities,
                'emirates' => $emirates,
                'advisors' => $advisors,
                'teams' => $teams,
                'quoteDocuments' => fn () => array_values($quoteDocuments->toArray()),
                'documentTypes' => $documentTypes,
                'cdnPath' => $cdnPath,
                'domainPath' => $domainPath,
                'activities' => $activities,
                'customerAdditionalContacts' => $customerAdditionalContacts,
                'insuranceProviders' => $insuranceProviders,
                'planTypes' => HealthPlanTypeEnum::withLabels(),
                'lostReasons' => $lostReasons,
                'permissions' => [
                    'pa' => auth()->user()->hasRole(RolesEnum::PA),
                    'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                ],
                'hashCollapsibleStatuses' => $hashCollapsibleStatuses,
                'modelType' => $quoteType,
                'quoteTypeId' => $quoteTypeId,
                'notProductionApproval' => $notProductionApproval,
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
                'quoteRequest' => $paymentEntityModel,
                'payments' => $payments,
                'mainPayment' => $mainPayment,
                'paymentMethods' => $paymentMethods,
                'healthPlanTypes' => $healthPlanTypes,
                'sendPolicy' => (bool) $displaySendPolicyButton,
                'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::HealthManager),
                'embeddedProducts' => $embeddedProducts,
                'quoteType' => QuoteTypes::HEALTH,
                'paymentTooltipEnum' => PaymentTooltip::asArray(),
                'storageUrl' => storageUrl(),
                'can' => [
                    'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                    'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                    'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && ! auth()->user()->hasRole(RolesEnum::PA),
                    'isPA' => auth()->user()->hasRole(RolesEnum::PA),
                    'isAdvisor' => auth()->user()->hasRole(RolesEnum::EBPAdvisor) || auth()->user()->hasRole(RolesEnum::HealthAdvisor) || auth()->user()->hasRole(RolesEnum::RMAdvisor) || auth()->user()->hasRole(RolesEnum::CarAdvisor),
                ],
                'customerTypeEnum' => CustomerTypeEnum::asArray(),
                'industryType' => $industryType,
                'UBOsDetails' => $uboDetails,
                'UBORelations' => $uboRelations,
                'enums' => [
                    'travelQuoteEnum' => TravelQuoteEnum::asArray(),
                ],
                'bookPolicyDetails' => $bookPolicyDetails,
                'sendUpdateEnum' => $sendUpdateEnum,
                'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
                'staleDays' => $record->stale_at,
                now()->diffInDays(Carbon::parse("$record->stale_at")),
                'noteDocumentType' => $noteDocumentType,
                'quoteNotes' => $quoteNotes,
                'clientInquiryLogs' => $this->crudService->getInquiryLogs($this->genericModel->modelType, $record->uuid) ?? [],
                'isNewPaymentStructure' => $isNewPaymentStructure,
                'linkedQuoteDetails' => $linkedQuoteDetails,
                'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
                'clientInquiryLogs' => $clientInquiryLogs,
                'paymentDocument' => $paymentDocument,
            ]);
        } else {
            return view('shared.show', compact([
                'paymentLink',
                'record',
                'model',
                'payments',
                'mainPayment',
                'paymentMethods',
                'insuranceProviders',
                'paymentEntityModel',
                'customTitles',
                'customTableList',
                'advisors',
                'leadStatuses',
                'lostReasons',
                'selectedLostReasonId',
                'model_name',
                'allowedDuplicateLOB',
                'audits',
                'activities',
                'isRenewalUser',
                'isNewBusinessUser',
                'autoAllocationDisabled',
                'isQuoteDocumentEnabled',
                'quoteDocuments',
                'displaySendPolicyButton',
                'customerAdditionalContacts',
                'quoteType',
                'quoteTypeId',
                'tiers',
                'access',
            ]));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response klm[jo]
     */
    public function edit($id)
    {
        $isRenewalUser = Auth::user()->isRenewalUser();
        if ($isRenewalUser && strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Car)) {
            $renewalAdvisors = $this->crudService->fillRenewalData($this->genericModel);
        }
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        $model = $this->genericModel;
        $dropdownSource = [];
        $customTitles = [];
        $customLists = [];
        foreach ($model->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'select')) {
                $data = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $data;
            }
            if (str_contains($value, 'customTable')) {
                $data = $this->dropdownSourceService->getCustomDropdownList($property, $record[0]->id);
                $customLists[$property] = $data;
            }
        }

        if ($this->genericModel->modelType == quoteTypeCode::Health) {
            return inertia('HealthQuote/Form', [
                'quote' => $record,
                'genderOptions' => $this->crudService->getGenderOptions(),
                'dropdownSource' => $dropdownSource,
                'isRenewalUser' => $isRenewalUser,
                'model' => json_encode($model->properties),
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Home) {
            return inertia('HomeQuote/Form', [
                'quote' => $record,
                'homePossessionTypeEnum' => HomePossessionType::asArray(),
                'dropdownSource' => $dropdownSource,
                'isRenewalUser' => $isRenewalUser,
                'model' => json_encode($model->properties),
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Car) {
            $dropdownSource['car_make_id'] = $this->getCarMakeDropdown();
            $customerAddressData = $this->customerService->getCustomerAddressData($record);
            $courierQuoteResponse = app(MACRMService::class)->getCourierQuoteStatus($record->uuid, QuoteTypeId::Car);
            $courierQuoteStatus = isset($courierQuoteResponse['data']['status'])
                ? $courierQuoteResponse['data']['status']
                : 'Pending';

            return inertia('PersonalQuote/Car/Form', [
                'quote' => $record,
                'homePossessionTypeEnum' => HomePossessionType::asArray(),
                'dropdownSource' => $dropdownSource,
                'isRenewalUser' => $isRenewalUser,
                'model' => json_encode($model->properties),
                'customerAddressData' => $customerAddressData,
                'courierQuoteStatus' => $courierQuoteStatus,
            ]);
        }

        return view('shared.edit', compact(['record', 'model', 'dropdownSource', 'customTitles', 'customLists', 'isRenewalUser']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $modelPropertiesList = json_decode($request->all()['model'], true);
        $modelType = json_decode($request->all()['modelType'], true);
        $validateArray = [];
        if ($modelType == 'Home') {
            $modelSkipPropertiesList = (json_decode($request->get('modelSkipProperties'), true)) ? json_decode($request->get('modelSkipProperties'), true) : $request->get('modelSkipProperties');
            $validateArray = $this->homeQuoteService->getValidationArray($modelPropertiesList, $request, $modelSkipPropertiesList);
        } else {
            if ($modelType == quoteTypeCode::Car) {
                $validateArray = $this->carQuoteService->getValidationArray($request);
            } else {
                $jsonDecodeSkipProps = json_decode($request->get('modelSkipProperties'), true);
                $modelSkipPropertiesList = is_null($jsonDecodeSkipProps) ? explode(',', $request->get('modelSkipProperties')) : json_decode($request->get('modelSkipProperties'), true);

                foreach ($modelPropertiesList as $property => $value) {
                    $strPosUpdateCheck = (is_null($jsonDecodeSkipProps)) || ! strpos($modelSkipPropertiesList['update'], $property);
                    if (is_null($jsonDecodeSkipProps) && in_array($property, $modelSkipPropertiesList)) {
                        continue;
                    }
                    if (strpos($value, 'required') && $property != 'id' && $property != 'code' && $property != 'email' && $property != 'mobile_no' && $property != 'car_value_tier' && $modelSkipPropertiesList != null && $strPosUpdateCheck) {
                        $validateArray[$property] = 'required';
                    }
                }
            }
        }

        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;
        $this->validate($request, $validateArray);
        app(CustomerAddressService::class)->validateAddress($request);
        $response = $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);

        // check if request addressObj is not empty then insert/update the address of user in customer address table
        $customerId = $this->customerService->getCustomerIdByEmail($request->email);

        if (($request->has('addressObj') && ! empty(array_filter((array) $request->input('addressObj')))) && $modelType == quoteTypeCode::Car) {
            $lead = CarQuote::where('uuid', $id)->first();
            if ($lead) {
                $this->carQuoteService->sendAddressNotificationToCustomer($lead, $request->input('addressObj'));
                app(CustomerAddressService::class)->createOrUpdateCustomerAddress($request->input('addressObj'), $customerId, $id);
                SyncCourierQuoteWithMacrm::dispatch($lead, QuoteTypeId::Car);
            }
        }
        if (! is_null($response) && ! $response) {
            return redirect('/quotes/'.strtolower(str_replace('"', '', $request->modelType)).'/'.$id.'/edit')->with('error', json_decode($request->modelType, true).' has not been updated');
        }

        return redirect('/quotes/'.strtolower(str_replace('"', '', $request->modelType)).'/'.$id)->with('success', json_decode($request->modelType, true).' has been updated');
    }

    public function cardsViewHome(Request $request)
    {

        $userTeams = auth()->user()->getUserTeams(auth()->id())->toArray();

        $isManagerOrDeputy = auth()->user()->hasAnyRole([RolesEnum::HomeManager, RolesEnum::HomeRenewalManager]);

        $newBusinessTeam = in_array(TeamNameEnum::HOME, $userTeams);
        $renewalsTeam = in_array(TeamNameEnum::HOME_RENEWALS, $userTeams);

        $areBothTeamsPresent = $newBusinessTeam && $renewalsTeam;

        if (($request->is_renewal === null && $areBothTeamsPresent) || ($request->is_renewal === null && $isManagerOrDeputy)) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        } elseif ($request->is_renewal === null && $newBusinessTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::noText]);
        } elseif ($request->is_renewal === null && $renewalsTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        }

        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::NewLead, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        $newBusiness = [
            QuoteStatusEnum::NewLead => 0,
            QuoteStatusEnum::Quoted => 1,
            QuoteStatusEnum::FollowedUp => 2,
            QuoteStatusEnum::PaymentPending => 3,
            QuoteStatusEnum::TransactionApproved => 4,
            QuoteStatusEnum::PolicyIssued => 5,
        ];

        $renewals = [
            QuoteStatusEnum::Allocated => 0,
            QuoteStatusEnum::Quoted => 1,
            QuoteStatusEnum::FollowedUp => 2,
            QuoteStatusEnum::PaymentPending => 3,
            QuoteStatusEnum::TransactionApproved => 4,
            QuoteStatusEnum::PolicyIssued => 5,
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

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
        } elseif (array_intersect([TeamNameEnum::HOME], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::Allocated,
                QuoteStatusEnum::InNegotiation,
            ])->values()->toArray();
        } elseif (array_intersect([TeamNameEnum::HOME_RENEWALS], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::NewLead,
                QuoteStatusEnum::InNegotiation,
            ])->values()->toArray();
        }

        $totalLeads = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;

        foreach ($quotes as $item) {
            $totalLeads += $item['data']['total_leads'];
        }

        $advisors = $this->crudService->getAdvisorsByModelType(quoteTypeCode::Home);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Home);

        return inertia('HomeQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'teams' => $userTeams,
            'quoteTypeId' => QuoteTypes::HOME->id(),
            'quoteType' => QuoteTypes::HOME->value,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $totalLeads : HomeQuoteRepository::getData(true, true),
            'areBothTeamsPresent' => $areBothTeamsPresent || $isManagerOrDeputy ? true : false,
            'is_renewal' => ($areBothTeamsPresent || $isManagerOrDeputy ? 'Yes' : $renewalsTeam) ? 'Yes' : ($newBusinessTeam ? 'No' : null),
        ]);
    }

    private function setModelType(Request $request)
    {
        $url = strpos($request->fullUrl(), '?') ? explode('?', $request->fullUrl())[0] : $request->fullUrl();
        if (strpos($url, 'health')) {
            $this->genericModel->modelType = 'Health';
        }
        if (strpos($url, 'travel')) {
            $this->genericModel->modelType = 'Travel';
        }
        if (strpos($url, 'teams')) {
            $this->genericModel->modelType = 'Teams';
        }
        if (strpos($url, 'car')) {
            $this->genericModel->modelType = 'Car';
        }
        if (strpos($url, 'life')) {
            $this->genericModel->modelType = 'Life';
        }
        if (strpos($url, 'home')) {
            $this->genericModel->modelType = 'Home';
        }
        if (strpos($url, 'business')) {
            $this->genericModel->modelType = 'Business';
        }
        if (strpos($url, 'leadstatus')) {
            $this->genericModel->modelType = 'LeadStatus';
        }
        if (strpos($url, 'pet')) {
            $this->genericModel->modelType = 'Pet';
        }
    }

    private function fillModelByModelType($type, Request $request)
    {
        $modelType = json_decode($request->get('modelType'), true) ?? $type;

        if ($modelType == null) {
            $modelType = $request->get('modelType');
        }
        $ignoreModelTypes = [quoteTypeCode::Pet, quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Yacht];
        if (! in_array($modelType, $ignoreModelTypes) && $modelType != null) {
            $quoteTypes = 'Health,Car,Travel,Life,Home,Business';
            $serviceType = str_contains($quoteTypes, ucwords($modelType)) ? strtolower($modelType).'QuoteService' : lcfirst(ucwords($modelType)).'Service';
            $this->genericModel->properties = $this->{$serviceType}->fillModelProperties();
            $this->genericModel->skipProperties = $this->{$serviceType}->fillModelSkipProperties();
            $this->genericModel->searchProperties = $this->{$serviceType}->fillModelSearchProperties();
        }
    }

    public function getDropdownSourceNameForDisplay($modelType, $propertyName, $recordId)
    {
        $data = $this->dropdownSourceService->getDropdownSource($propertyName);
        $recordName = '';
        $record = $this->crudService->getEntity($modelType, $recordId);
        foreach ($data as $item) {
            if ($item->id == $record[$propertyName]) {
                $recordName = $item->text ?? $item->name;
            }
        }

        return $recordName;
    }

    public function carQuotePlanDetails($quoteId, $planId)
    {
        $quotePlans = $this->carQuoteService->getQuotePlans($quoteId);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $quoteId);
        $paymentEntityModel = $this->{strtolower($this->genericModel->modelType).'QuoteService'}->getEntityPlain($record->id);

        $access = $this->carQuoteService->updatedAccessAgainstPaymentStatus($paymentEntityModel, $record);

        $updatedAtAudit = '';
        $auditLog = AuditRepository::select('updated_at')
            ->where('auditable_id', $record->id)
            ->where('auditable_type', 'App\Models\CarQuote')
            ->latest()
            ->first();
        if ($auditLog) {
            $updatedAtAudit = $auditLog->updated_at;
        }

        $isPlanUpdateActive = $this->applicationStorageService->getIsActiveByKey('IMCRM_CAR_QUOTE_PLANS_EDIT_IS_DISABLED');
        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans;

            return view('shared.plan_details', compact(['listQuotePlans', 'quoteId', 'planId', 'isPlanUpdateActive', 'access', 'updatedAtAudit']));
        }
    }

    public function travel_plan_details($quoteId, $planId)
    {
        $quotePlans = $this->travelQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans;
            foreach ($listQuotePlans as $listQuotePlan) { // Main
                if ($listQuotePlan->id == $planId) {
                    $listQuotePlansMembers = $listQuotePlan->memberPremiumBreakdown;
                    $listQuotePlanName = $listQuotePlan->name;
                    $providerCode = $listQuotePlan->providerCode;
                    $providerName = $listQuotePlan->providerName;
                    $travelType = $listQuotePlan->travelType;
                    $actualPremium = $listQuotePlan->actualPremium;
                    $discountPremium = $listQuotePlan->discountPremium;
                    $listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
                    $listQuotePlanBenefitstravelInconvenienceCover = $listQuotePlan->benefits->travelInconvenienceCover;
                    $listQuotePlanBenefitsemergencyMedicalCover = $listQuotePlan->benefits->emergencyMedicalCover;
                    $listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
                    $listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
                    $listQuotePlanBenefitsCovid19 = $listQuotePlan->benefits->covid19;
                    $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;

                    foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail) {
                        $listQuotePlanBenefitsPolicyDetailLink = $listQuotePlanBenefitsPolicyDetail->link;
                    }
                }
            }

            $modelName = quoteTypeCode::Travel;

            return view('shared.plan_details', compact([
                'listQuotePlanName',
                'providerCode',
                'providerName',
                'travelType',
                'actualPremium',
                'discountPremium',
                'listQuotePlanBenefitsInclusions',
                'listQuotePlanBenefitsExclusions',
                'listQuotePlanBenefitsFeatures',
                'listQuotePlanBenefitsCovid19',
                'listQuotePlanBenefitsPolicyDetails',
                'listQuotePlanBenefitsPolicyDetailLink',
                'modelName',
                'listQuotePlansMembers',
                'listQuotePlanBenefitstravelInconvenienceCover',
                'listQuotePlanBenefitsemergencyMedicalCover',
            ]));
        }
    }

    public function health_plan_details($quoteId, $planId)
    {
        $quotePlans = $this->healthQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quote->plans;
            foreach ($listQuotePlans as $listQuotePlan) { // Main
                if ($listQuotePlan->id == $planId) {
                    $listQuotePlanName = $listQuotePlan->name;
                    $providerCode = $listQuotePlan->providerCode;
                    $providerName = $listQuotePlan->providerName;
                    $actualPremium = $listQuotePlan->actualPremium;
                    $discountPremium = $listQuotePlan->discountPremium;
                    $listQuotePlanBenefitsInpatient = isset($listQuotePlan->benefits->inpatient) ? $listQuotePlan->benefits->inpatient : [];
                    $listQuotePlanBenefitsOutpatient = isset($listQuotePlan->benefits->outpatient) ? $listQuotePlan->benefits->outpatient : [];
                    $listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
                    $listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
                    $listQuotePlanBenefitsCoInsurance = $listQuotePlan->benefits->coInsurance;
                    $listQuotePlanBenefitsRegionCover = $listQuotePlan->benefits->regionCover;
                    $listQuotePlanBenefitsMaternityCover = $listQuotePlan->benefits->maternityCover;
                    $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;
                    $members = isset($listQuotePlan->memberPremiumBreakdown) ? $listQuotePlan->memberPremiumBreakdown : [];
                    foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail) {
                        $listQuotePlanBenefitsPolicyDetailLink = $listQuotePlanBenefitsPolicyDetail->link;
                    }
                    $isManualPlan = isset($listQuotePlan->isManualPlan) ? $listQuotePlan->isManualPlan : false;
                }
            }
            $modelName = quoteTypeCode::Health;

            return view('shared.plan_details', compact([
                'listQuotePlanName',
                'providerCode',
                'providerName',
                'actualPremium',
                'discountPremium',
                'listQuotePlanBenefitsInpatient',
                'listQuotePlanBenefitsExclusions',
                'listQuotePlanBenefitsFeatures',
                'listQuotePlanBenefitsPolicyDetails',
                'listQuotePlanBenefitsPolicyDetailLink',
                'modelName',
                'listQuotePlanBenefitsCoInsurance',
                'listQuotePlanBenefitsRegionCover',
                'listQuotePlanBenefitsMaternityCover',
                'members',
                'planId',
                'quoteId',
                'isManualPlan',
                'listQuotePlanBenefitsOutpatient',
            ]));
        }
    }

    public function wcuAssign(Request $request)
    {
        $result = $this->healthQuoteService->assignWCU($request);
        if (count($result) > 0) {
            $msg = '';
            foreach ($result as $item) {
                $msg = $msg.'Lead with Ref-ID '.$item['leadId'].' is not assigned. <span style="color:black;">Reason : '.$item['msg'].'</span> <br>';
            }
            Log::warning('WCU Assignment Failed for '.$request->modelType.' Quote , selected id was '.$request->selectTmLeadId);

            return Redirect::back()->with('message', $msg);
        }
        $assignedUserName = $this->userService->getUserNameById((int) $request->assigned_to_id_new);

        return Redirect::back()->with('success', $request->modelType.' Leads has been Assigned To '.$assignedUserName);
    }

    public function manualLeadAssign(Request $request)
    {
        $isValidRequest = $this->crudService->validateRequest($request->modelType, $request);
        if ($isValidRequest != 'true') {
            return redirect()->back()->with('error', $isValidRequest);
        }
        $assignedUser = $this->userService->getUserById((int) $request->assigned_to_id_new);
        if (! $assignedUser) {
            return Redirect::back()->with('message', 'Selected advisor does not exist in the system!');
        }
        $assignmentResult = $this->{strtolower($request->modelType).'QuoteService'}->processManualLeadAssignment($request);
        if (count($assignmentResult) > 0) {
            $msg = '';
            foreach ($assignmentResult as $assignmentResultItem) {
                $msg = $msg.' Lead with Ref-ID'.$assignmentResultItem['leadId'].' is not assigned, Reason : '.$assignmentResultItem['msg'].' <br>';
            }
            Log::warning('Manual Lead Assignment Failed for '.$request->modelType.' Quote , selected id was '.$request->selectTmLeadId);

            return Redirect::back()->with('message', $msg);
        } else {
            $quoteIds = explode(',', $request->selectTmLeadId);
            foreach ($quoteIds as $id) {
                $quoteData = $this->getQuoteObject($request->modelType, $id);
                if ($quoteData && $quoteData->payment_status_id === PaymentStatusEnum::AUTHORISED) {
                    app(NotificationService::class)->paymentStatusUpdate($request->modelType, $quoteData->uuid);
                }
            }

            return Redirect::back()->with('success', $request->modelType.' Leads has been Assigned To '.$assignedUser->name);
        }
    }

    public function addCarQuotePlan(Request $request)
    {
        $quoteUuId = $request->quoteUuId;
        $insuranceProviders = $this->lookupService->getAllInsuranceProviders();
        $listQuotePlans = $this->carQuoteService->getPlans($quoteUuId);

        return view('components.car-quote-add-plan', compact('quoteUuId', 'insuranceProviders', 'listQuotePlans'));
    }

    public function healthTeamAssign(Request $request)
    {
        $selectedTeam = $request->get('assign_team');

        $entityId = $request->get('entityId');

        info('Inside Health Team Assign with team : '.$selectedTeam.'  and entity Id : '.$entityId);

        $lead = $this->healthQuoteService->getEntityPlain($entityId);

        if (! $lead || $lead == null) {
            return redirect()->to('/quotes/health')->with('message', ' Lead not found. Please try again.');
        }

        $isAssigned = $this->healthQuoteService->assignHealthTeam($request, $lead);

        if (Auth::user()->isHealthWCUAdvisor() && $lead->quote_status_id == QuoteStatusEnum::Qualified && $selectedTeam != quoteTypeCode::GM && $isAssigned) {
            return redirect()->to('/quotes/health')->with('success', ' Lead Team has been assigned successfully');
        }

        if ($selectedTeam == quoteTypeCode::GM && $isAssigned) {
            return redirect()->to('/quotes/health')->with('success', ' Lead has been Converted And Assigned To Group Medical Team');
        }
        if ($selectedTeam != quoteTypeCode::GM && $isAssigned) {
            return redirect()->to('/quotes/health/'.$lead->uuid)->with('success', ' Lead has been Assigned To '.strtoupper($selectedTeam).' Team');
        }
    }

    public function updateLeadStatus(UpdateLeadStatusRequest $request)
    {
        // Car Quote: validate next_followup_date
        if (strtolower($request->modelType) == strtolower(quoteTypeCode::Car)) {
            $lead = $this->carQuoteService->getEntityPlain($request->leadId);
            if ($request->leadStatus == QuoteStatusEnum::TransactionApproved || $request->leadStatus == QuoteStatusEnum::PolicyIssued) {
                // MS: dispatch sib work flow
                SyncSIBContactJob::dispatch($lead);
            }

            if (in_array($request->leadStatus, [QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer])) {
                if (isset($request->quote_uuid)) {
                    $record = $this->crudService->getEntity($request->modelType, $request->quote_uuid);
                    $this->activityService->createActivity($request, $record);
                }
            }

            if ($request->leadStatus == QuoteStatusEnum::IMRenewal) {
                // MS: Send email
                if (isset($request->leadId)) {
                    CarRenewalEmailJob::dispatch($lead);
                }
            }
        }

        $result = $this->crudService->updateQuoteStatus($request);
        $entity = $result['entity'];
        if ($request->leadStatus == QuoteStatusEnum::TransactionApproved) {
            $plainEntity = $this->getQuoteObject($request->modelType, $request->leadId);
            $this->crudService->calculateScore($plainEntity, $request->modelType);
        }

        // courtesy email
        $lobs = [quoteTypeCode::Business];
        // Update payment allocation status
        app(CentralService::class)->updatePaymentAllocation($request->modelType, $request->quote_uuid);
        if ($entity->health_team_type != null && $entity->quote_status_id == QuoteStatusEnum::Qualified) {
            return redirect()->to('/quotes/health')->with('success', ' Lead status has been updated successfully');
        }

        if ($result['activityResponse']) {
            return redirect()->to('/quotes/'.strtolower($request->modelType).'/'.$entity->uuid)->with('success', 'Status updated successfully & Activity has been created');
        }

        return redirect()->to('/quotes/'.strtolower($request->modelType).'/'.$entity->uuid)->with('success', ' Lead Status has been Updated');
    }

    public function carPlanManualProcess(Request $request)
    {
        $response = $this->carQuoteService->carPlanModify($request);

        if ($response == 200 || $response == 201) {
            return redirect()->back()->with('success', 'Car Plan has been saved');
        } else {
            return redirect()->back()->with('error', $response);
        }
    }

    public function loadMoreRecords(Request $request)
    {
        if ($request->has('modelType') && $request->modelType && $request->status) {
            // $results = getDataAgainstEveryStatus($request->modelType, $request);
            $results = getDataAgainstStatus($request->modelType, $request->status, $request);

            if (in_array($request->modelType, [quoteTypeCode::Health, quoteTypeCode::Business, quoteTypeCode::Travel, quoteTypeCode::Home, quoteTypeCode::Life, quoteTypeCode::Pet, quoteTypeCode::Cycle, quoteTypeCode::Yacht])) {
                return $results;
            }

            $html = '';
            if ($results) {
                foreach ($results['leads_list'] as $result) {
                    $html .= ' <li data-block-id="53" class="drag-item">
                    <div class="lead-block rotten">
                        <div class="lead-title">'.$result->code.'</div>
                        <span class="float-right">
                        <a target="_blank" href="/quotes/'.strtolower($request->modelType).'/'.$result->uuid.'"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                        </span>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                        '.$result->first_name.' '.$result->last_name.'
                        </div>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-building font-1" aria-hidden="true"></i>
                        '.$result->company_name.'
                        </div>
                        <div class="pad-5"></div>
                        <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp;'.$result->premium.'
                        </div>
                    </div>
                </li>';
                }
            }

            return $html;
        }
    }

    public function getLeadHistory(Request $request)
    {
        $leadHistory = $this->crudService->getLeadAuditHistory($request->modelType, $request->recordId);

        return $leadHistory;
    }

    /**
     * @return mixed
     */
    public function getLeadHistoryLogs(Request $request)
    {
        return $this->crudService->getLeadHistoryLogs($request->quoteTypeId, $request->recordId);
    }

    public function searchLead(Request $request)
    {
        if ($request->has('modelType') && $request->modelType && $request->term && $request->status) {
            $results = getDataAgainstSearchTerm($request->modelType, $request);

            if (in_array($request->modelType, [quoteTypeCode::Health, quoteTypeCode::Business, quoteTypeCode::Travel, quoteTypeCode::Home, quoteTypeCode::Life])) {
                return $results;
            }

            $html = '';
            if ($results) {
                foreach ($results['leads_list'] as $result) {
                    $html .= ' <li data-block-id="53" class="drag-item">
                    <div class="lead-block rotten">
                        <div class="lead-title">'.$result->code.'</div>
                        <span class="float-right">
                        <a target="_blank" href="/quotes/'.strtolower($request->modelType).'/'.$result->uuid.'"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                        </span>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                        '.$result->first_name.' '.$result->last_name.'
                        </div>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-building font-1" aria-hidden="true"></i>
                        '.$result->company_name.'
                        </div>
                        <div class="pad-5"></div>
                        <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp;'.$result->premium.'
                        </div>
                    </div>
                </li>';
                }
            }

            return $html;
        }
    }

    public function createDuplicate(Request $request)
    {
        $this->crudService->createDuplicate($request);

        return redirect()->to('/quotes/'.strtolower($request->parentType).'/'.$request->entityUId)->with('success', ' Lead has been Duplicated');
    }

    public function createActivity(Request $request)
    {
        $record = $this->{strtolower($request->modelType).'QuoteService'}->getEntityPlain($request->entityId);
        $this->activityService->createActivity($request, $record);
        if (isset($request->isActivityView)) {
            return redirect()->to('/activities/')->with('success', ' Activity has been Created');
        }

        return redirect()->to('/quotes/'.strtolower($request->parentType).'/'.$request->entityUId)->with('success', ' Activity has been Created');
    }

    public function carAssumptionsUpdate(Request $request)
    {
        $quoteID = $this->carQuoteService->carAssumptionsUpdateProcess($request);

        if ($quoteID) {
            return redirect()->back()->with('success', 'Car Assumptions has been updated');
        }
    }

    public function addNoteForCustomer(Request $request)
    {
        $response = $this->sendNotesToCustomer($request);

        if ($response == 201) {
            $noteId = $this->notesForCustomerService->addCustomerNote($request);
        } else {
            return redirect()->back()->with('message', $response);
        }

        if ($noteId) {
            return redirect()->back()->with('success', 'Notes to customer has been sent.');
        }
    }

    public function sendNotesToCustomer(Request $request)
    {
        return $this->notesForCustomerService->notesSendToCustomer($request);
    }

    public function updateQuotePolicy(UpdatePolicyDetailRequest $policyDetailRequest)
    {
        $request = (object) $policyDetailRequest->validated();
        $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
        if (! $quoteModel) {
            return redirect()->back()->with('success', 'Error Updating Policy Details.');
        }
        info('Quote Code: '.$quoteModel->code.' fn: updateQuotePolicy called');
        $quoteModel->update([
            'policy_number' => $request->quote_policy_number ?? '',
            'policy_issuance_date' => isset($request->quote_policy_issuance_date) ? Carbon::parse($request->quote_policy_issuance_date)->format('Y-m-d') : null,
            'policy_start_date' => isset($request->quote_policy_start_date) ? Carbon::parse($request->quote_policy_start_date)->format('Y-m-d') : null,
            'policy_expiry_date' => isset($request->quote_policy_expiry_date) ? Carbon::parse($request->quote_policy_expiry_date)->format('Y-m-d') : null,
            'price_vat_not_applicable' => $request->price_vat_notapplicable ?? '',
            'price_vat_applicable' => $request->price_vat_applicable ?? '',
            'price_with_vat' => $request->amount_with_vat ?? '',
            'vat' => $request->vat ?? '',
            'insurer_quote_number' => $request->quote_plan_insurer_quote_number ?? '',
            'policy_issuance_status_id' => $request->quote_policy_issuance_status ?? null,
            'policy_issuance_status_other' => $request->quote_policy_issuance_status_other ?? '',
        ]);

        if (! empty(request()->quote_policy_issuance_status) && request()->price_with_vat <= 0 && empty(request()->quote_policy_number)) {
            $quoteModel->update([
                'quote_status_id' => QuoteStatusEnum::PolicyPending,
            ]);
        }
        // store policy issuer
        $payment = $quoteModel->payments()->mainLeadPayment()->first();
        if ($payment) {
            $payment->policy_issuer_id = auth()->id();
            $payment->save();
        }

        $centralService = app(CentralService::class);
        $centralService->synchronizePaymentInformation($quoteModel);
        $centralService->updateQuoteInformation($request->modelType, $request->quote_id);
        info('Quote Code: '.$quoteModel->code.' Policy detail updated successfully');
        if (in_array($quoteModel->quote_status_id, [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::PolicySentToCustomer])) {
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($quoteModel, $payment, $request->modelType);
            info('Quote Code: '.$quoteModel->code.' BIN Generated for transactional leads');
        }

        return redirect()->back()->with([
            'success' => 'Policy details has been updated.',
        ]);
    }

    public function manualPlanToggle(Request $request)
    {
        $response = $this->{strtolower($request->modelType).'QuoteService'}->updateManualPlansBulk($request);

        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            return redirect()->back()->with('success', 'Plan has been updated');
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }

            return redirect()->back()->with('message', $responseMessage);
        }
    }

    /**
     * export selected plans to PDF.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportCarPdf($quoteType, ExportPlansPdfRequest $request)
    {
        $response = $this->carQuoteService->exportPlansPdf($quoteType, $request->validated());

        if (isset($response['error'])) {
            return redirect()->back()->with('message', $response['error']);
        }

        $pdf = $response['pdf'];

        return $pdf->download($response['name']);
    }

    /**
     * export selected plans to PDF.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportHealthPdf($quoteType, ExportPlansPdfRequest $request)
    {
        $response = $this->healthQuoteService->exportPlansPdf($quoteType, $request->validated());

        if (isset($response['error'])) {
            return redirect()->back()->with('message', $response['error']);
        }

        $pdf = $response['pdf'];

        return $pdf->download($response['name']);
    }

    public function destroyDocument($quoteType, $quoteUuId, $id)
    {
        $document = QuoteDocument::find($id);

        if (! $document) {
            return redirect()->back()->with('message', 'Document not found');
        }

        $document->delete();

        return redirect()->back()->with('message', 'Document has been deleted.');
    }

    public function storePayment(StorePaymentRequest $request)
    {
        $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
        if (! $quoteModel) {
            return response()->json(['success' => false]);
        }
        $paymentInformation = [
            'collection_type' => $request->collection_type,
            'captured_amount' => $request->captured_amount,
            'payment_methods_code' => $request->payment_methods,
            'payment_status_id' => PaymentStatusEnum::DRAFT,
            'plan_id' => ! empty($request->plan_id) ? $request->plan_id : null,
            'insurance_provider_id' => ! empty($request->insurance_provider_id) ? $request->insurance_provider_id : null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ];

        $count = $quoteModel->payments->count();
        if ($request->send_update_id) { // it will check if the payment is added from send update.
            $paymentInformation['send_update_log_id'] = $request->send_update_id;
            $paymentInformation['code'] = app(SendUpdateLogService::class)->getPaymentCode($quoteModel->code);
            // it will make $quoteModel as SendUpdateLog model.
            $quoteModel = SendUpdateLogRepository::getLogById($request->send_update_id);
        } else {
            $paymentInformation['code'] = ($count > 0) ? $quoteModel->code.'-'.$count : $quoteModel->code;
        }

        if ($request->reference) {
            $paymentInformation['reference'] = $request->reference;
        }
        if ($request->payment_methods != PaymentMethodsEnum::CreditCard && $request->payment_methods != PaymentMethodsEnum::InsureNowPayLater) {
            $paymentInformation['authorized_at'] = now();
        }

        $payment = Payment::create($paymentInformation);
        $quoteModel->payments()->save($payment);
        $paymentLog = new PaymentStatusLog([
            'current_payment_status_id' => PaymentStatusEnum::DRAFT,
            'payment_code' => $paymentInformation['code'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentLog->save();
        if (! $request->send_update_id) { // it will check if the payment is added from send update.
            $quoteModel->quote_status_id = QuoteStatusEnum::PaymentPending;
        }
        $quoteModel->save();

        return back()->with('success', 'Payment has been created');
    }

    public function updatePayment(Request $request)
    {
        $paymentInformation = [
            'collection_type' => $request->collection_type,
            'captured_amount' => $request->captured_amount,
            'payment_methods_code' => $request->payment_methods,
            'insurance_provider_id' => $request->insurance_provider_id,
            'updated_by' => $request->user()->id,
        ];
        if ($request->reference) {
            $paymentInformation['reference'] = $request->reference;
        }
        $payment = Payment::where('code', $request->paymentCode)->first();
        if (! $payment) {
            return back()->with('message', 'Payment record not found');
        }
        $payment->update($paymentInformation);

        return back()->with('success', 'Payment has been updated');
    }

    public function sendEmailOneClickBuy(Request $request)
    {
        Log::info('sendEmailOneClickBuy OCB email sending started for quote uuid: '.$request->quote_uuid);

        // get Car quote by uuid using model
        $carQuote = CarQuote::where('uuid', $request->quote_uuid)->first();

        $previousAdvisor = null;
        if (! empty($carQuote->previous_advisor_id)) {
            $previousAdvisor = $this->userService->getUserById($carQuote->previous_advisor_id);
        }

        // CHECK NUMBER OF PLAN AND SEND RESPECTIVE 'ONE CLICK BUY' EMAIL TO CUSTOMER
        $listQuotePlans = $this->carQuoteService->getPlans($request->quote_uuid, true, true);

        info('sendEmailOneClickBuy OCB email plans fetched for quote uuid: '.$request->quote_uuid);

        $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;
        $emailTemplateId = (int) $this->crudService->getOcbCustomerEmailTemplate($quotePlansCount);

        $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

        $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

        $emailData = (new CarEmailService($this->sendEmailCustomerService))->buildEmailData($carQuote, $listQuotePlans, $previousAdvisor, $tierR->id);

        info('sendEmailOneClickBuy OCB email data built for quote uuid: '.$request->quote_uuid);

        $responseCode = $this->sendEmailCustomerService->sendRenewalsOcbEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy');

        if ($responseCode == 201) {
            info('sendEmailOneClickBuy OCB email sent to customer for quote uuid: '.$request->quote_uuid);

            return response()->json(['success' => 'OCB email sent to customer']);
        } else {
            Log::info('sendEmailOneClickBuy OCB email sending failed for quote uuid: '.$request->quote_uuid.' with error code: '.$responseCode);

            return response()->json(['error' => 'OCB email sending failed, please try again. Error Code: '.$responseCode], 500);
        }
    }

    public function sendOCBEmailNB(Request $request, $quoteType, $quoteUuId)
    {
        if ($quoteUuId) {

            $ocbEmailJob = QuoteTypes::getName(QuoteTypes::getIdFromValue($quoteType))?->ocbEmailJob();
            if ($ocbEmailJob) {
                Log::info("sendOCBEmailNB OCB email sending started for quote uuid: {$quoteUuId}");
                dispatch(new $ocbEmailJob($quoteUuId, null));
                info("sendOCBEmailNB OCB email Job dispatched for quote uuid: {$quoteUuId}");
            }

            return response()->json(['success' => 'OCB NB email sent to customer !']);
        } else {
            Log::info('sendOCBEmailNB OCB email quote uuid not found');

            return response()->json(['error' => 'OCB email sending failed, please try again.'], 500);
        }
    }

    public function manualTierAssignment(Request $request)
    {
        $selectedLeadId = $request->selectedLeadId;
        $selectedTierId = $request->selectedTierId;
        $entityCode = $request->entityCode;
        info('manualTierAssignment  -- selected Tier Id : '.$selectedLeadId.' , selected Lead is : '.$entityCode.', requested by '.auth()->user()->email);
        CarQuote::where('id', $selectedLeadId)->update([
            'tier_id' => $selectedTierId,
            'cost_per_lead' => Tier::where('id', $selectedTierId)->get()->first()->cost_per_lead,
            'updated_at' => now(),
            'updated_by' => auth()->user()->email,
            'advisor_id' => null,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'quote_status_date' => now(),
            'is_renewal_tier_email_sent' => 0,
        ]);
    }

    public function toggleEmbeddedProduct(Request $request)
    {
        $quoteTypeId = $this->activityService->getQuoteTypeId(strtolower(ucfirst($request->modelType)));

        return $this->crudService->toggleSelection($request, $quoteTypeId);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    private function getCarMakeDropdown()
    {
        return CarMake::select('id', 'text', 'code')->where('is_active', true)->get();
    }

    public function riskRatingDetails($quoteType, $uuid)
    {
        $quoteModel = $this->getQuoteObjectBy($quoteType, $uuid, 'uuid');
        $response = $this->crudService->scoreBreakdown($quoteModel, $quoteType);

        return $response;
    }
}
