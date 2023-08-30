<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Enums\HealthTeamType;
use App\Enums\HomePossessionType;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Exports\HealthQuotesExport;
use App\Facades\Capi;
use App\Http\Requests\ExportPlansPdfRequest;
use App\Jobs\CarRenewalEmailJob;
use App\Jobs\SyncSIBContactJob;
use App\Models\CarQuote;
use App\Models\Emirate;
use App\Models\GenericModel;
use App\Models\HealthPlanType;
use App\Models\LeadAllocation;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Models\QuoteDocument;
use App\Models\Tier;
use App\Models\User;
use App\Repositories\InsuranceProviderRepository;
use App\Services\ActivitiesService;
use App\Services\ApplicationStorageService;
use App\Services\BusinessQuoteService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\EmailDataService;
use App\Services\EmailStatusService;
use App\Services\HealthQuoteService;
use App\Services\HomeQuoteService;
use App\Services\LeadAllocationService;
use App\Services\LifeQuoteService;
use App\Services\LookupService;
use App\Services\NotesForCustomerService;
use App\Services\PetQuoteService;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Services\TeamService;
use App\Services\TravelQuoteService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
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
    protected $petQuoteService;
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

    use GenericQueriesAllLobs;

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
        PetQuoteService $petQuoteService,
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
    ) {
        $this->genericModel = new GenericModel();
        $this->healthQuoteService = $healthService;
        $this->teamsService = $teamsService;
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->carQuoteService = $carQuoteService;
        $this->travelQuoteService = $travelQuoteService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->homeQuoteService = $homeQuoteService;
        $this->businessQuoteService = $businessQuoteService;
        $this->petQuoteService = $petQuoteService;
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
        $renewalAdvisors = [];
        $isNewBusinessUser = false;
        $isManualAllocationAllowed = false;
        if (strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Car)) {
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
        $userMaxCap = 0;
        $todayAssignmentCount = 0;
        if (strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Car) && auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $advisorAllocationRecord = LeadAllocation::where('user_id', auth()->user()->id)->first();
            $userMaxCap = $advisorAllocationRecord->max_capacity == -1 ? 'No Limit' : ($advisorAllocationRecord->max_capacity ?? 0);
            $todayAssignmentCount = ''.($advisorAllocationRecord->auto_assignment_count ?? 0).' / '.$advisorAllocationRecord->manual_assignment_count ?? 0 .'';
        }
        $tiers = Tier::where('is_active', 1)->get();
        //Checking if the loggedIn user is Renewal User
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
            $this->crudService->fillNewBusinessData($this->genericModel);
            $renewalAdvisors = $this->crudService->getNewBusinessAdvisorsByModelType($this->genericModel->modelType);
        }
        // Getting the data for grid based on the model type
        $gridData = $this->crudService->getGridData($this->genericModel, $request);
        // Getting the data for the advisor dropdown based on the model type
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        // Checking if the loggedIn user has Manager or Deputy Role
        $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
        $isLeadPool = Auth::user()->isLeadPool();
        $quoteTypeId = $this->activityService->getQuoteTypeId(strtolower($this->genericModel->modelType));
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
        if ($this->genericModel->modelType == quoteTypeCode::Health && in_array($this->genericModel->modelType, newUi())) {
            $gridData = $gridData->simplePaginate(10)->withQueryString();

            $quote_status = $dropdownSource['quote_status_id'];

            return inertia('HealthQuote/Index', [
                'quotes' => $gridData,
                'leadStatuses' => $quote_status,
                'advisors' => $advisors,
            ]);
        }

        // inertia rendering for home quote
        if ($this->genericModel->modelType == quoteTypeCode::Home && in_array($this->genericModel->modelType, newUi())) {
            $gridData = $gridData->simplePaginate(10)->withQueryString();

            $quote_status = $dropdownSource['quote_status_id'];

            return inertia('HomeQuote/Index', [
                'quotes' => $gridData,
                'leadStatuses' => $quote_status,
                'advisors' => $advisors,
                'isManualAllocationAllowed' => $isManualAllocationAllowed,
            ]);
        }

        if ($request->ajax()) {
            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
        }

        return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy', 'isRenewalUser', 'renewalAdvisors', 'isNewBusinessUser', 'isLeadPool', 'isCarLeadAllocationOn', 'tiers', 'isManualAllocationAllowed', 'userMaxCap', 'todayAssignmentCount'));
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
            $this->crudService->fillNewBusinessData($this->genericModel);
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

        if ($this->genericModel->modelType == quoteTypeCode::Health && in_array($this->genericModel->modelType, newUi())) {
            return inertia('HealthQuote/Form', [
                'dropdownSource' => $dropdownSource,
                'model' => json_encode($model->properties),
                'genderOptions' => $this->crudService->getGenderOptions(),
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Home && in_array($this->genericModel->modelType, newUi())) {
            return inertia('HomeQuote/Create', [
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
        $validateArray = [];
        if ($modelType !== quoteTypeCode::Health && $modelType !== quoteTypeCode::Home) {
            foreach ($modelPropertiesList as $property => $value) {
                if (strpos($value, 'required') && $property != 'id' && ! strpos($modelSkipPropertiesList['create'], $property)) {
                    $validateArray[$property] = 'required';
                }
            }
        }
        $request->dob = isset($request->dob) ? Carbon::parse($request->dob)->format('Y-m-d') : null;

        // new ui enabled
        if ($modelType == quoteTypeCode::Home && in_array($modelType, newUi())) {
            $validateArray = [];
            if ($request->has('first_name')) {
                $this->validate($request, [
                    'first_name' => 'required|max:255',
                ]);
            }
            if ($request->has('last_name')) {
                $this->validate($request, [
                    'last_name' => 'required|max:255',
                ]);
            }
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
        $record = $this->crudService->saveModelByType($modelType, $request);

        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return Redirect::back()->with('message', $record->message)->withInput();
        } else {
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
            //Fix for trailing slash when loading plans through jQuery
            return redirect(request()->url());
        }
        $quoteType = strtolower($this->genericModel->modelType);
        $quoteTypeId = $this->activityService->getQuoteTypeId($quoteType);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        abort_if(! $record, 404);
        $autoAllocationDisabled = $this->lookupService->getApplicationStorageValue('LEAD_ALLOCATION_JOB_SWITCH');
        if (strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Health) && Auth::user()->isHealthWCUAdvisor() && $record->wcu_id != Auth::user()->id && $autoAllocationDisabled == '1') {
            abort(403, 'Unauthorized action.');
        }
        $paymentEntityModel = $this->{strtolower($this->genericModel->modelType).'QuoteService'}->getEntityPlain($record->id);
        $payments = $paymentEntityModel->payments;
        $paymentMethods = $this->lookupService->getPaymentMethods();
        $isRenewalUser = false;
        $isNewBusinessUser = false;
        $model = $this->genericModel;
        $model_name = $this->genericModel->modelType.'Quote';

        $customTitles = $customTableList = [];
        if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            $isRenewalUser = true;
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } elseif (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            $isNewBusinessUser = true;
            $this->crudService->fillNewBusinessData($this->genericModel);
            $renewalAdvisors = $this->crudService->getNewBusinessAdvisorsByModelType($this->genericModel->modelType);
        }
        $leadStatuses = $this->dropdownSourceService->getDropdownSource('quote_status_id', $quoteTypeId);
        $lostReasons = $this->lookupService->getLostReasons();
        $selectedLostReasonId = '';
        if (strtolower($this->genericModel->modelType) != 'teams' && strtolower($this->genericModel->modelType) != 'leadstatus') {
            $selectedLostReasonId = $this->crudService->getSelectedLostReason($this->genericModel->modelType, $record->id);
        }
        $advisors = [];
        if (strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Health) && ($record->health_team_type == HealthTeamType::EBP ||
            $record->health_team_type == HealthTeamType::RM_NB || $record->health_team_type == HealthTeamType::RM_SPEED)) {
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
        $serviceType = str_contains($quoteTypes, ucwords($model->modelType)) ? strtolower($model->modelType).'QuoteService' : lcfirst(ucwords($model->modelType)).'Service';
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
            ];
            array_push($activities, $updatedActivity);
        }
        $audits = [];
        $emailStatuses = $this->emailStatusService->getEmailStatus($quoteTypeId, $record->id);
        $notesForCustomers = $this->notesForCustomerService->getNotesForCustomer($quoteTypeId, $record->id);
        $advisor = isset($record->advisor_id) ? $this->userService->getUserById((int) $record->advisor_id) : null;
        $isQuoteDocumentEnabled = $this->quoteDocumentService->isEnabled($model->modelType);
        $quoteDocuments = $this->quoteDocumentService->getQuoteDocuments($model->modelType, $record->id);
        $displaySendPolicyButton = $this->quoteDocumentService->showSendPolicyButton($record, $quoteDocuments, $quoteTypeId);
        $customerAdditionalContacts = $this->customerService->getAdditionalContacts($record->customer_id, $record->mobile_no);
        $tiers = $this->lookupService->getTierR();

        $access = $this->carQuoteService->updatedAccessAgainstPaymentStatus($paymentEntityModel, $record);

        if ($this->genericModel->modelType == quoteTypeCode::Car) { // Car plans to display on detail view
            $ecomCarInsuranceQuoteUrl = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL');
            $listQuotePlans = null;
            $carQuotePlanAddons = $this->carQuoteService->getCarQuotePlanAddons($id);
            $listQuotePlans = $this->carQuoteService->getPlans($id);
            $vehicleTypes = $this->lookupService->getVehicleTypes();
            $trimList = $this->lookupService->getTrimListByCarModel($record->car_model_id);
            $yearsOfManufacture = $this->lookupService->getYearsOfManufacture();
            $carMakeText = $record->car_make_id_text ? $record->car_make_id_text : '';
            $carModelText = $record->car_model_id_text ? $record->car_model_id_text : '';
            $this->carQuoteService->addOrUpdateQuoteViewCount($record);

            $daysAfterCapturedPayment = null;
            if (($capturedPaymentDate = PaymentStatusLog::where(['quote_type_id' => QuoteTypeId::Car,
                'quote_request_id' => $record->id,
                'current_payment_status_id' => PaymentStatusEnum::CAPTURED,
            ])->first())) {
                $daysAfterCapturedPayment = Carbon::now()->diffInDays(Carbon::parse($capturedPaymentDate->created_at));
            }

            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'listQuotePlans', 'customTableList',
                'ecomCarInsuranceQuoteUrl', 'carQuotePlanAddons', 'vehicleTypes', 'leadStatuses',
                'lostReasons', 'selectedLostReasonId', 'model_name', 'allowedDuplicateLOB', 'audits',
                'activities', 'advisors', 'isRenewalUser', 'isNewBusinessUser', 'emailStatuses',
                'yearsOfManufacture', 'notesForCustomers', 'quoteType', 'quoteTypeId', 'trimList', 'autoAllocationDisabled',
                'paymentEntityModel', 'payments', 'paymentMethods', 'isQuoteDocumentEnabled', 'quoteDocuments', 'displaySendPolicyButton', 'customerAdditionalContacts',
                'carMakeText', 'carModelText', 'advisor', 'tiers', 'daysAfterCapturedPayment', 'access',
            ]));
        }

        if ($this->genericModel->modelType == quoteTypeCode::Travel) { // Travel plans to display on detail view
            $ecomTravelInsuranceQuoteUrl = config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL');
            $listQuotePlans = '';
            $quotePlans = $this->travelQuoteService->getQuotePlans($id);
            if (isset($quotePlans->message) && $quotePlans->message != '') {
                $listQuotePlans = $quotePlans->message;
            } else {
                if (gettype($quotePlans) != 'string') {
                    $listQuotePlans = $quotePlans->quotes->plans;
                } else {
                    $listQuotePlans = $quotePlans;
                }
            }

            $membersDetail = $this->travelQuoteService->getMembersDetail($record->id);

            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'listQuotePlans', 'customTableList',
                'leadStatuses', 'lostReasons', 'selectedLostReasonId', 'membersDetail', 'model_name',
                'allowedDuplicateLOB', 'audits', 'activities', 'advisors', 'isRenewalUser',
                'isNewBusinessUser', 'ecomTravelInsuranceQuoteUrl', 'quoteType', 'autoAllocationDisabled',
                'paymentEntityModel', 'payments', 'paymentMethods', 'emailStatuses',
                'isQuoteDocumentEnabled', 'quoteDocuments', 'displaySendPolicyButton', 'customerAdditionalContacts',
                'quoteTypeId', 'tiers', 'access',
            ]));
        }

        if ($this->genericModel->modelType == quoteTypeCode::Home && in_array($this->genericModel->modelType, newUi())) {
            $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
            $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();
            $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $domainPath = config('constants.AFIA_WEBSITE_DOMAIN');
            $notProductionApproval = ! auth()->user()->hasRole(RolesEnum::PA);

            return inertia('HomeQuote/Show', [
                'quote' => $record,
                'allowedDuplicateLOB' => $allowedDuplicateLOB,
                'leadStatuses' => array_values($leadStatuses->toArray()),
                'advisors' => $advisors,
                'cdnPath' => $cdnPath,
                'domainPath' => $domainPath,
                'activities' => $activities,
                'customerAdditionalContacts' => $customerAdditionalContacts,
                'lostReasons' => $lostReasons,
                'permissions' => [
                    'pa' => auth()->user()->hasRole(RolesEnum::PA),
                ],
                'quoteStatusEnum' => QuoteStatusEnum::asArray(),
                'modelType' => $quoteType,
                'notProductionApproval' => $notProductionApproval,
                'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
                'quoteRequest' => $paymentEntityModel,
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Health && in_array($this->genericModel->modelType, newUi())) { // Health plans to display on detail view
            $listQuotePlans = [];
            $quotePlans = $this->healthQuoteService->getQuotePlans($id);
            if (isset($quotePlans->message) && $quotePlans->message != '') {
                $listQuotePlans = [];
            } else {
                if (gettype($quotePlans) != 'string') {
                    $listQuotePlans = $quotePlans->quote->plans;
                } else {
                    $listQuotePlans = [];
                }
            }
            $membersDetail = $this->healthQuoteService->getMembersDetail($record->id);
            $memberCategories = $this->lookupService->getMemberCategories();
            $salaryBands = $this->lookupService->getSalaryBands();
            $ecomDetails = $this->healthQuoteService->getEcomDetails($record);
            $ecomHealthInsuranceQuoteUrl = config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL');
            $leadStatuses = $this->healthQuoteService->statusesToDisplay($leadStatuses, $record);

            $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
            $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

            $documentTypes = $this->quoteDocumentService->getQuoteDocumentsForUpload(QuoteTypeId::Health);

            $documentTypes = collect($documentTypes)->groupBy('category');

            $quoteDocuments = $quoteDocuments->map(function ($quoteDocument) {
                $quoteDocument->created_by_name = isset($quoteDocument->createdBy->name) ? $quoteDocument->createdBy->name : null;

                return $quoteDocument;
            });

            $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $domainPath = config('constants.AFIA_WEBSITE_DOMAIN');
            $insuranceProviders = $this->lookupService->getAllInsuranceProviders();

            $notProductionApproval = ! auth()->user()->hasRole(RolesEnum::PA);
            $payments->load(['paymentStatus', 'healthPlan.insuranceProvider', 'paymentStatusLog', 'paymentMethod']);
            $paymentEntityModel->load(['plan.insuranceProvider']);

            $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Health);

            $payments->each(function ($payment) {
                $allow = $payment->payment_status_id != PaymentStatusEnum::CAPTURED && $payment->payment_status_id != PaymentStatusEnum::AUTHORISED && ! auth()->user()->hasRole(RolesEnum::PA);
                $payment->copy_link_button = $allow && optional($payment->paymentMethod)->code == PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID;
                $payment->edit_button = $allow && $payment->payment_status_id != PaymentStatusEnum::PAID;
                $payment->approve_button = optional($payment->paymentMethod)->code != PaymentMethodsEnum::CreditCard && $payment->payment_status_id != PaymentStatusEnum::PAID && $payment->payment_status_id != PaymentStatusEnum::CAPTURED
                    && ! auth()->user()->hasRole(RolesEnum::PA);

                $payment->approved_button = $payment->payment_status_id == PaymentStatusEnum::PAID;
            });

            $paymentMethods = $paymentMethods->map(function ($paymentMethod) {
                return [
                    'value' => $paymentMethod->code,
                    'label' => $paymentMethod->name,
                ];
            });

            $insuranceProviders = $insuranceProviders?->map(function ($paymentMethod) {
                return [
                    'value' => $paymentMethod->id,
                    'label' => $paymentMethod->text,
                ];
            })->sortBy('label')->values();

            $healthPlanTypes = HealthPlanType::where('is_active', 1)->select('id', 'text')->get();

            return inertia('HealthQuote/Show', [
                'quote' => $record,
                'genderOptions' => $this->crudService->getGenderOptions(),
                'allowedDuplicateLOB' => $allowedDuplicateLOB,
                'leadStatuses' => array_values($leadStatuses->toArray()),
                'ecomDetails' => $ecomDetails,
                'membersDetail' => $membersDetail,
                'memberCategories' => $memberCategories,
                'salaryBands' => $salaryBands,
                'listQuotePlans' => $listQuotePlans,
                'ecomHealthInsuranceQuoteUrl' => $ecomHealthInsuranceQuoteUrl,
                'nationalities' => $nationalities,
                'emirates' => $emirates,
                'advisors' => $advisors,
                'quoteDocuments' => array_values($quoteDocuments->toArray()),
                'documentTypes' => $documentTypes,
                'cdnPath' => $cdnPath,
                'domainPath' => $domainPath,
                'activities' => $activities,
                'customerAdditionalContacts' => $customerAdditionalContacts,
                'insuranceProviders' => $insuranceProviders,
                'insuranceProviders' => $insuranceProviders,
                'lostReasons' => $lostReasons,
                'permissions' => [
                    'pa' => auth()->user()->hasRole(RolesEnum::PA),
                ],
                'quoteStatusEnum' => QuoteStatusEnum::asArray(),
                'modelType' => $quoteType,
                'notProductionApproval' => $notProductionApproval,
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
                'isBetaUser' => auth()->user()->hasRole(RolesEnum::BetaUser),
                'quoteRequest' => $paymentEntityModel,
                'payments' => $payments,
                'paymentMethods' => $paymentMethods,
                'healthPlanTypes' => $healthPlanTypes,
                'sendPolicy' => (bool) $displaySendPolicyButton,
                'can' => [
                    'approve_payments' => auth()->user()->can(PermissionsEnum::ApprovePayments),
                    'edit_payments' => auth()->user()->can(PermissionsEnum::PaymentsEdit),
                    'create_payments' => auth()->user()->can(PermissionsEnum::PaymentsCreate) && $paymentEntityModel->plan && ! auth()->user()->hasRole(RolesEnum::PA),
                    'isPA' => auth()->user()->hasRole(RolesEnum::PA),
                    'isAdvisor' => auth()->user()->hasRole(RolesEnum::EBPAdvisor) || auth()->user()->hasRole(RolesEnum::HealthAdvisor) || auth()->user()->hasRole(RolesEnum::RMAdvisor),
                ],
            ]);
        } else {
            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'customTableList', 'advisors', 'leadStatuses', 'lostReasons',
                'selectedLostReasonId', 'model_name', 'allowedDuplicateLOB', 'audits', 'activities', 'isRenewalUser',
                'isNewBusinessUser', 'autoAllocationDisabled', 'isQuoteDocumentEnabled', 'quoteDocuments',
                'displaySendPolicyButton', 'customerAdditionalContacts', 'quoteType', 'quoteTypeId', 'tiers', 'access',
            ]));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response        klm[jo]
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

        if ($this->genericModel->modelType == quoteTypeCode::Health && in_array($this->genericModel->modelType, newUi())) {
            return inertia('HealthQuote/Form', [
                'quote' => $record,
                'genderOptions' => $this->crudService->getGenderOptions(),
                'dropdownSource' => $dropdownSource,
                'isRenewalUser' => $isRenewalUser,
                'model' => json_encode($model->properties),
            ]);
        }

        if ($this->genericModel->modelType == quoteTypeCode::Home && in_array($this->genericModel->modelType, newUi())) {
            return inertia('HomeQuote/Edit', [
                'quote' => $record,
                'homePossessionTypeEnum' => HomePossessionType::asArray(),
                'dropdownSource' => $dropdownSource,
                'isRenewalUser' => $isRenewalUser,
                'model' => json_encode($model->properties),
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
            if ($modelType == quoteTypeCode::Car && Auth::user()->hasRole(RolesEnum::CarManager)) {
                $validateArray['renewal_batch'] = 'required';
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
        $response = $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);
        if (! is_null($response) && ! $response) {
            return redirect('/quotes/'.strtolower(str_replace('"', '', $request->modelType)).'/'.$id.'/edit')->with('error', json_decode($request->modelType, true).' has not been updated');
        }

        return redirect('/quotes/'.strtolower(str_replace('"', '', $request->modelType)).'/'.$id)->with('success', json_decode($request->modelType, true).' has been updated');
    }

    public function cardsViewHome(Request $request)
    {
        $quotes = [];
        $quotes[] = [
            'id' => 8,
            'title' => 'New Lead',
            'data' => getDataAgainstStatus('Home', 8),
        ];
        $quotes[] = [
            'id' => 2,
            'title' => 'Quoted',
            'data' => getDataAgainstStatus('Home', 2),
        ];
        $quotes[] = [
            'id' => 31,
            'title' => 'Qualified',
            'data' => getDataAgainstStatus('Home', 31),
        ];
        $quotes[] = [
            'id' => 25,
            'title' => 'In Negotiation',
            'data' => getDataAgainstStatus('Home', 25),
        ];
        $quotes[] = [
            'id' => 26,
            'title' => 'Application Pending',
            'data' => getDataAgainstStatus('Home', 26),
        ];
        $quotes[] = [
            'id' => 28,
            'title' => 'Payment Pending',
            'data' => getDataAgainstStatus('Home', 28),
        ];
        $quotes[] = [
            'id' => 36,
            'title' => 'Application Submitted',
            'data' => getDataAgainstStatus('Home', 36),
        ];
        $quotes[] = [
            'id' => 15,
            'title' => 'Transaction Approved',
            'data' => getDataAgainstStatus('Home', 15),
        ];
        $quotes[] = [
            'id' => 29,
            'title' => 'Policy Documents Pending',
            'data' => getDataAgainstStatus('Home', 29),
        ];

        return inertia('HomeQuote/Cards', [
            'quotes' => $quotes,
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
        $quoteTypes = 'Health,Car,Travel,Life,Home,Business,Pet';
        $serviceType = str_contains($quoteTypes, ucwords($modelType)) ? strtolower($modelType).'QuoteService' : lcfirst(ucwords($modelType)).'Service';
        $this->genericModel->properties = $this->{$serviceType}->fillModelProperties();
        $this->genericModel->skipProperties = $this->{$serviceType}->fillModelSkipProperties();
        $this->genericModel->searchProperties = $this->{$serviceType}->fillModelSearchProperties();
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

        //echo "<pre>"; print_r($quotePlans); exit;die();

        $createdAt = '';
        $updatedAt = '';
        $auditLog = \DB::table('audits')
            ->select('created_at', 'updated_at')
            ->where('auditable_id', $record->id)
            ->where('auditable_type', 'App\Models\CarQuote')
            ->latest()->first();
        if ($auditLog) {
            $createdAt = $auditLog->created_at;
            $updatedAt = $auditLog->updated_at;        
        }
        //echo $auditLog->created_at."fffgf";
        /*DB::table('audits')
            ->select('audits.*', 'users.name')
            ->join('users', 'audits.user_id', 'users.id')
            ->where('auditable_id', $record->id)
            ->where('auditable_type', 'App\Models\CarQuote')
            ->orderBy('created_at', 'desc')
            ->get();
        */
        //echo $record->id; exit;

        $isPlanUpdateActive = $this->applicationStorageService->getIsActiveByKey('IMCRM_CAR_QUOTE_PLANS_EDIT_IS_DISABLED');
        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans;

            return view('shared.plan_details', compact(['listQuotePlans', 'quoteId', 'planId', 'isPlanUpdateActive', 'access', 'createdAt', 'updatedAt']));
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
                'listQuotePlanName', 'providerCode', 'providerName', 'travelType',
                'actualPremium', 'discountPremium', 'listQuotePlanBenefitsInclusions',
                'listQuotePlanBenefitsExclusions', 'listQuotePlanBenefitsFeatures', 'listQuotePlanBenefitsCovid19',
                'listQuotePlanBenefitsPolicyDetails', 'listQuotePlanBenefitsPolicyDetailLink', 'modelName', 'listQuotePlansMembers',
                'listQuotePlanBenefitstravelInconvenienceCover', 'listQuotePlanBenefitsemergencyMedicalCover',
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
                'listQuotePlanName', 'providerCode', 'providerName',
                'actualPremium', 'discountPremium', 'listQuotePlanBenefitsInpatient',
                'listQuotePlanBenefitsExclusions', 'listQuotePlanBenefitsFeatures',
                'listQuotePlanBenefitsPolicyDetails', 'listQuotePlanBenefitsPolicyDetailLink', 'modelName',
                'listQuotePlanBenefitsCoInsurance', 'listQuotePlanBenefitsRegionCover',
                'listQuotePlanBenefitsMaternityCover', 'members', 'planId', 'quoteId', 'isManualPlan', 'listQuotePlanBenefitsOutpatient',
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

    public function updateLeadStatus(Request $request)
    {
        if (! $request->leadStatus) {
            return redirect()->back()->with('message', 'Please select lead status and try again.');
        }
        if (strtolower($request->modelType) == strtolower(quoteTypeCode::Health)) {
            $lead = $this->healthQuoteService->getEntityPlain($request->get('leadId'));
            if (! $lead) {
                return redirect()->back()->with('message', 'Lead not found please try again.');
            }
            if (($lead->health_team_type == null || $lead->health_team_type == quoteTypeCode::WCU) && $request->leadStatus == QuoteStatusEnum::Qualified) {
                return redirect()->back()->with('message', 'Please select team type before moving to QUALIFIED status');
            }
        }
        if ($request->leadStatus == QuoteStatusEnum::Lost) {
            $this->validate($request, [
                'lostReason' => 'required',
            ]);
        }
        if ($request->leadStatus == QuoteStatusEnum::TransactionApproved) {
            $this->validate($request, [
                'trans_code' => 'required',
            ]);
        }
        // Car Quote: validate next_followup_date
        if (strtolower($request->modelType) == strtolower(quoteTypeCode::Car)) {
            $lead = $this->carQuoteService->getEntityPlain($request->leadId);
            if ($request->leadStatus == QuoteStatusEnum::TransactionApproved || $request->leadStatus == QuoteStatusEnum::PolicyIssued) {
                // MS: dispatch sib work flow
                SyncSIBContactJob::dispatch($lead);
            }
            if (
                $request->leadStatus == QuoteStatusEnum::FollowupCall ||
                $request->leadStatus == QuoteStatusEnum::Interested ||
                $request->leadStatus == QuoteStatusEnum::NoAnswer
            ) {
                $dateFormat = config('constants.DATETIME_DISPLAY_FORMAT');
                $this->validate($request, [
                    'next_followup_date' => 'required',
                    'next_followup_date' => 'date_format:'.$dateFormat.'|after_or_equal:'.date($dateFormat),
                    'notes' => 'required',
                ]);
                if (isset($request->quote_uuid)) {
                    $record = $this->crudService->getEntity($request->modelType, $request->quote_uuid);
                    $this->activityService->createActivity($request, $record);
                }
            }
            if ($request->leadStatus == QuoteStatusEnum::IMRenewal) {
                if (! isset($request->tier_id)) {
                    $this->validate($request, [
                        'tier_id' => 'required',
                    ]);
                }

                // MS: Send email
                if (isset($request->leadId)) {
                    CarRenewalEmailJob::dispatch($lead);
                }
            }
        }
        $oldEntity = $this->crudService->getEntityByUUID($request->quote_uuid, $request->modelType);
        $entity = $this->crudService->updateQuoteStatus($request);
        // courtesy email
        $lobs = [quoteTypeCode::Business];
        if ($oldEntity->quote_status_id != $entity->quote_status_id && $entity->quote_status_id == QuoteStatusEnum::TransactionApproved && ! in_array($request->modelType, $lobs)) {
            $quoteTypeId = $this->activityService->getQuoteTypeId(strtolower($request->modelType));
            $quoteData['quoteTypeId'] = $quoteTypeId;
            $quoteData['quoteUID'] = $request->quote_uuid;

            $response = Capi::request('/api/v1-trigger-courtesy-email-sib-workflow', 'post', $quoteData);

            info('Courtesy Email CAPI Response - : '.json_encode($response));
        }
        if ($entity->health_team_type != null && $entity->quote_status_id == QuoteStatusEnum::Qualified) {
            return redirect()->to('/quotes/health')->with('success', ' Lead status has been updated successfully');
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
            $results = getDataAgainstEveryStatus($request->modelType, $request);

            // and newUi is true
            if (in_array($request->modelType, [quoteTypeCode::Health, quoteTypeCode::Business, quoteTypeCode::Travel, quoteTypeCode::Home, quoteTypeCode::Life]) && in_array($request->modelType, newUi())) {
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

            if (in_array($request->modelType, [quoteTypeCode::Health, quoteTypeCode::Business, quoteTypeCode::Travel, quoteTypeCode::Home, quoteTypeCode::Life]) && in_array($request->modelType, newUi())) {
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

    public function updateQuotePolicy(Request $request)
    {
        $model = '\\App\\Models\\'.ucwords($request->modelType).'Quote';
        $quoteModel = $model::where('id', $request->quote_id)->first();
        if (! $quoteModel) {
            return redirect()->back()->with('success', 'Error Updating Policy Details.');
        }

        $quoteModel->update([
            'policy_number' => $request->quote_policy_number,
            'policy_issuance_date' => Carbon::parse($request->quote_policy_issuance_date)->format('Y-m-d'),
            'policy_start_date' => Carbon::parse($request->quote_policy_start_date)->format('Y-m-d'),
            'renewal_expiry_date' => Carbon::parse($request->quote_policy_expiry_date)->format('Y-m-d'),
            'premium' => $request->quote_premium,
        ]);

        return redirect()->back()->with('success', 'Quote Policy Detail has been updated.');
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

    /**
     * export health leads to excel sheet.
     */
    public function exportHealthLeads(Request $request)
    {
        $created_at_start = $request->created_at_start;
        $created_at_end = $request->created_at_end;

        if (Carbon::parse($created_at_start)->diffInDays(Carbon::parse($created_at_end)) > 120) {
            return back()->with('error', 'Maximum of 120 days (created date) are allowed to be exported.');
        }

        $query = $this->crudService->getGridData($this->genericModel, $request);

        return (new HealthQuotesExport($query))->download('Health-List.xlsx');
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

    public function storePayment(Request $request)
    {
        $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
        if (! $quoteModel) {
            return response()->json(['success' => false]);
        }
        $code = 'P-'.strtoupper(substr(uniqid(''), 0, 8));
        $paymentInformation = [
            'code' => $code,
            'collection_type' => $request->collection_type,
            'captured_amount' => $request->captured_amount,
            'payment_methods_code' => $request->payment_methods,
            'payment_status_id' => PaymentStatusEnum::PENDING,
            'plan_id' => $request->plan_id,
            'insurance_provider_id' => $request->insurance_provider_id,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ];
        if ($request->reference) {
            $paymentInformation['reference'] = $request->reference;
        }
        if ($request->payment_methods != PaymentMethodsEnum::CreditCard) {
            $paymentInformation['authorized_at'] = now();
        }
        $payment = Payment::create($paymentInformation);
        $quoteModel->payments()->save($payment);
        $paymentLog = new PaymentStatusLog([
            'current_payment_status_id' => PaymentStatusEnum::PENDING,
            'payment_code' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentLog->save();
        $quoteModel->quote_status_id = QuoteStatusEnum::PaymentPending;
        $quoteModel->save();

        return back()->with('success', 'Payment has been created');
    }

    public function updatePayment(Request $request)
    {
        $paymentInformation = [
            'collection_type' => $request->collection_type,
            'captured_amount' => $request->captured_amount,
            'payment_methods_code' => $request->payment_methods,
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
        Log::info('sendEmailOneClickBuy START');
        // CHECK NUMBER OF PLAN AND SEND RESPECTIVE 'ONE CLICK BUY' EMAIL TO CUSTOMER
        $listQuotePlans = $this->carQuoteService->getPlans($request->quote_uuid, true, true);
        $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;
        $emailTemplateId = (int) $this->crudService->getOcbCustomerEmailTemplate($quotePlansCount);

        $emailData = (object) [
            'quoteTypeId' => $request->quote_type_id,
            'quoteId' => $request->quote_id,
            'templateId' => $emailTemplateId,
            'quoteCdbId' => $request->quote_cdb_id,
            'customerName' => $request->customer_name,
            'customerEmail' => $request->customer_email,
            'previousPolicyExpiryDate' => $request->quote_previous_expiry_date,
            'currentlyInsuredWith' => $request->quote_currently_insured_with,
            'carMake' => $request->quote_car_make,
            'carModel' => $request->quote_car_model,
            'carManufactureYear' => $request->quote_car_year_of_manufacture,
            'previousPolicyNumber' => $request->quote_previous_policy_number,
            'advisorName' => $request->advisor_name,
            'advisorEmailAddress' => $request->advisor_email,
            'advisorMobileNo' => $request->advisor_mobile_no,
            'advisorLandlineNo' => $request->advisor_landline_no,
            'buttonUrl' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$request->quote_uuid,
            'listQuotePlans' => $listQuotePlans,
            'multipleQuoteUrl' => config('constants.AFIA_WEBSITE_DOMAIN').'/car-insurance/quote/'.$request->quote_uuid.'/'.'payment/?providerCode=',
            'quotePlansCount' => isset($quotePlansCount) ? $quotePlansCount : 0,
        ];

        $responseCode = $this->sendEmailCustomerService->sendOcbEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy');

        if ($responseCode == 201) {
            return response()->json(['success' => 'OCB email sent to customer']);
        } else {
            Log::info('sendEmailOneClickBuy ('.$request->quote_cdb_id.') OCB email sending failed Error Code: '.$responseCode);

            return response()->json(['error' => 'OCB email sending failed, please try again. Error Code: '.$responseCode], 500);
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
            'is_renewal_tier_email_sent' => 0,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
    }
}
