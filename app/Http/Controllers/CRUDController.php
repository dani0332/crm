<?php

namespace App\Http\Controllers;

use App\Enums\HealthTeamType;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\GenericModel;
use App\Models\InsuranceProvider;
use App\Models\User;
use App\Services\ActivitiesService;
use App\Services\BusinessQuoteService;
use Illuminate\Http\Request;
use App\Services\DropdownSourceService;
use App\Services\HealthQuoteService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\HomeQuoteService;
use App\Services\LifeQuoteService;
use App\Services\TeamService;
use App\Services\TravelQuoteService;
use App\Services\PetQuoteService;
use App\Services\UserService;
use App\Services\EmailStatusService;
use App\Services\ApplicationStorageService;
use App\Services\LeadAllocationService;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Config;
use DB;
use App\Services\LookupService;
use App\Services\NotesForCustomerService;
use App\Services\CustomerService;
use App\Services\SendEmailCustomerService;

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
        SendEmailCustomerService $sendEmailCustomerService
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
        //Checking if the loggedIn user is Renewal User
        $isRenewalUser = Auth::user()->isRenewalUser();
        if ($isRenewalUser && strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Car)) {
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } else if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {

            $isRenewalUser = true;
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } else if (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
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

        $dropdownSource = $customTitles = [];

        foreach ($this->genericModel->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                // Getting custom title for each property where title is mentioned in the property meta data
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'select')) {
                // Getting the dropdown source for each property where select is mentioned in the property meta data
                $dropdownValue = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $dropdownValue;
            }
        }
        $model = $this->genericModel;
        if ($request->ajax()) {
            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
            return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy', 'isRenewalUser', 'renewalAdvisors', 'isNewBusinessUser'));
        }
        return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy', 'isRenewalUser', 'renewalAdvisors', 'isNewBusinessUser'));
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
        } else if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            $isRenewalUser = true;
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } else if (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
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
        return view('shared.add', compact('model', 'dropdownSource', 'customTitles', 'isRenewalUser'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $modelPropertiesList = json_decode($request->get('model'), true);
        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        $modelType = json_decode($request->get('modelType'), true);
        $validateArray = [];
        if ($modelType == 'Home') {
            $validateArray = $this->homeQuoteService->getValidationArray($modelPropertiesList, $request, $modelSkipPropertiesList['create']);
        } else {
            foreach ($modelPropertiesList as $property => $value) {
                if (strpos($value, 'required') && $property != 'id' && !strpos($modelSkipPropertiesList['create'], $property)) {
                    $validateArray[$property] = 'required';
                }
            }
        }
        $this->validate($request, $validateArray);
        $record = $this->crudService->saveModelByType($modelType, $request);
        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return Redirect::back()->with('message', $record->message)->withInput();
        } else {
            if (!isset($record->quoteUID)) {
                return redirect('/quotes/' . strtolower($modelType))->with('success', ((str_contains(strtolower($modelType), 'team') ? 'Team' : (str_contains(strtolower($modelType), 'leadstatus') ? 'Lead Status' : $modelType))) . ' has been stored');
            } else {
                return redirect('/quotes/' . strtolower($modelType) . '/' . $record->quoteUID)->with('success', ((str_contains(strtolower($modelType), 'team') ? 'Team' : (str_contains(strtolower($modelType), 'leadstatus') ? 'Lead Status' : $modelType))) . ' has been stored');
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $quoteType = strtolower($this->genericModel->modelType);
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        if (!$record) abort(404);
        $autoAllocationDisabled = $this->lookupService->getApplicationStorageValue('LEAD_ALLOCATION_JOB_SWITCH');
        if(strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Health) &&  Auth::user()->isHealthWCUAdvisor() && $record->wcu_id != Auth::user()->id && $autoAllocationDisabled == '1'){
            abort(403, 'Unauthorized action.');
        }

        $isRenewalUser = false;
        $isNewBusinessUser = false;
        $model = $this->genericModel;
        $model_name = $this->genericModel->modelType . "Quote";
        $customTitles = $customTableList = [];
        if (Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
            $isRenewalUser = true;
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
        } else if (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
            $isNewBusinessUser = true;
            $this->crudService->fillNewBusinessData($this->genericModel);
            $renewalAdvisors = $this->crudService->getNewBusinessAdvisorsByModelType($this->genericModel->modelType);
        }
        $leadStatuses = $this->dropdownSourceService->getDropdownSource('quote_status_id');
        $lostReasons = $this->lookupService->getLostReasons();
        $selectedLostReasonId = '';
        if (strtolower($this->genericModel->modelType) != 'teams' && strtolower($this->genericModel->modelType) != 'leadstatus') {
            $selectedLostReasonId = $this->crudService->getSelectedLostReason($this->genericModel->modelType, $record->id);
        }
        $advisors  = [];
        if (strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Health) && ($record->health_team_type == HealthTeamType::EBP ||
            $record->health_team_type == HealthTeamType::RM_NB || $record->health_team_type == HealthTeamType::RM_SPEED)) {
            $advisors = $this->crudService->getEBPAndRMAdvisors();
        } else if (strtolower($this->genericModel->modelType) == 'business') {
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
        $serviceType = str_contains($quoteTypes, ucwords($model->modelType)) ? strtolower($model->modelType) . 'QuoteService' : lcfirst(ucwords($model->modelType)) . 'Service';
        $allowedDuplicateLOB = $this->crudService->getAllowedDuplicateLOB($model->modelType, $record->code);
        $activitiesData = $this->activityService->getActivityByLeadId($record->id, strtolower($model->modelType));
        $activities = [];
        foreach ($activitiesData as $activity) {
            $updatedActivity = array(
                'id' => $activity->id,
                'title' => $activity->title,
                'quote_request_id' => $activity->quote_request_id,
                'quote_type_id' => $activity->quote_type_id,
                'quote_uuid' => $activity->quote_uuid,
                'client_name' => $activity->client_name,
                'due_date' => $activity->due_date,
                'assignee' => User::where('id', $activity->assignee_id)->first()->name,
                'status' => $activity->status,
            );
            array_push($activities, $updatedActivity);
        }
        $audits = [];
        if ($this->genericModel->modelType == quoteTypeCode::Car) { // Car plans to display on detail view

            $ecomCarInsuranceQuoteUrl = Config::get('constants.ECOM_CAR_INSURANCE_QUOTE_URL');
            $listQuotePlans = NULL;
            $carQuotePlanAddons = $this->carQuoteService->getCarQuotePlanAddons($id);
            $listQuotePlans = $this->carQuoteService->getPlans($id);
            $entity = $this->carQuoteService->getQuoteByUuid($id);
            $vehicleTypes = $this->lookupService->getVehicleTypes();
            $trimList = $this->lookupService->getTrimListByCarModel($record->car_model_id);
            $yearsOfManufacture = $this->lookupService->getYearsOfManufacture();
            $emailStatuses = $this->emailStatusService->getEmailStatus(QuoteTypeId::Car, $entity->id);
            $notesForCustomers = $this->notesForCustomerService->getNotesForCustomer(QuoteTypeId::Car, $entity->id);
            $quoteTypeId = QuoteTypeId::Car;

            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'listQuotePlans', 'customTableList',
                'ecomCarInsuranceQuoteUrl', 'carQuotePlanAddons', 'vehicleTypes', 'leadStatuses',
                'lostReasons', 'selectedLostReasonId','model_name', 'allowedDuplicateLOB', 'audits',
                'activities', 'advisors','isRenewalUser', 'isNewBusinessUser', 'emailStatuses',
                'yearsOfManufacture','notesForCustomers', 'quoteTypeId','trimList', 'autoAllocationDisabled'
            ]));
        } else if ($this->genericModel->modelType == quoteTypeCode::Travel) { // Travel plans to display on detail view
            $ecomTravelInsuranceQuoteUrl = Config::get('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL');
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

            $members_detail = $this->travelQuoteService->getMembersDetail($record->id);

            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'listQuotePlans', 'customTableList',
                'leadStatuses', 'lostReasons', 'selectedLostReasonId', 'members_detail','model_name', 'allowedDuplicateLOB', 'audits', 'activities', 'advisors','isRenewalUser',
                'isNewBusinessUser', 'ecomTravelInsuranceQuoteUrl', 'quoteType', 'autoAllocationDisabled'
            ]));
        } else if ($this->genericModel->modelType == quoteTypeCode::Health) { // Health plans to display on detail view
            $listQuotePlans = '';
            $quotePlans = $this->healthQuoteService->getQuotePlans($id);
            if (isset($quotePlans->message) && $quotePlans->message != '') {
                $listQuotePlans = $quotePlans->message;
            } else {
                if (gettype($quotePlans) != 'string') {
                    $listQuotePlans = $quotePlans->quote->plans;
                } else {
                    $listQuotePlans = $quotePlans;
                }
            }
            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'listQuotePlans', 'customTableList',
                'leadStatuses', 'lostReasons', 'selectedLostReasonId', 'model_name', 'allowedDuplicateLOB', 'audits', 'advisors', 'activities', 'isRenewalUser',
                'isNewBusinessUser', 'autoAllocationDisabled'
            ]));
        } else {
            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'customTableList', 'advisors', 'leadStatuses', 'lostReasons', 'selectedLostReasonId', 'model_name', 'allowedDuplicateLOB', 'audits', 'activities', 'isRenewalUser',
                'isNewBusinessUser', 'autoAllocationDisabled'
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
        return view('shared.edit', compact(['record', 'model', 'dropdownSource', 'customTitles', 'customLists', 'isRenewalUser']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $modelPropertiesList = json_decode($request->all()['model'], true);
        $modelType = json_decode($request->all()['modelType'], true);
        $modelSkipPropertiesList = json_decode($request->get('modelSkipProperties'), true);
        $validateArray = [];
        if ($modelType == 'Home') {
            $validateArray = $this->homeQuoteService->getValidationArray($modelPropertiesList, $request, $modelSkipPropertiesList);
        } else {
            Log::info("update quote with id " . $id . " and modelproperties " . json_encode($modelPropertiesList));
            foreach ($modelPropertiesList as $property => $value) {
                if (strpos($value, 'required') && $property != 'id' && $property != 'code' && $property != 'email' && $property != 'mobile_no' && $modelSkipPropertiesList != null && !strpos($modelSkipPropertiesList['update'], $property)) {
                    $validateArray[$property] = 'required';
                }
            }
        }
        $this->validate($request, $validateArray);
        $this->crudService->updateModelByType(json_decode($request->modelType, true), $request, $id);
        return redirect('/quotes/' . strtolower(str_replace('"', '', $request->modelType)) . '/' . $id)->with('success', json_decode($request->modelType, true) . ' has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function setModelType(Request $request)
    {
        $url = strpos($request->fullUrl(), '?') ? explode('?', $request->fullUrl())[0] : $request->fullUrl();
        if (strpos($url, 'health')) $this->genericModel->modelType = 'Health';
        if (strpos($url, 'travel')) $this->genericModel->modelType = 'Travel';
        if (strpos($url, 'teams')) $this->genericModel->modelType = 'Teams';
        if (strpos($url, 'car')) $this->genericModel->modelType = 'Car';
        if (strpos($url, 'life')) $this->genericModel->modelType = 'Life';
        if (strpos($url, 'home')) $this->genericModel->modelType = 'Home';
        if (strpos($url, 'business')) $this->genericModel->modelType = 'Business';
        if (strpos($url, 'leadstatus')) $this->genericModel->modelType = 'LeadStatus';
        if (strpos($url, 'pet')) $this->genericModel->modelType = 'Pet';
    }

    private function fillModelByModelType($type, Request $request)
    {
        $modelType = json_decode($request->get('modelType'), true) ?? $type;
        if ($modelType == null) $modelType = $request->get('modelType');
        $quoteTypes = 'Health,Car,Travel,Life,Home,Business,Pet';
        $serviceType = str_contains($quoteTypes, ucwords($modelType)) ? strtolower($modelType) . 'QuoteService' : lcfirst(ucwords($modelType)) . 'Service';
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
        $isPlanUpdateActive = $this->applicationStorageService->getKeyValue('IMCRM_CAR_QUOTE_PLANS_EDIT_IS_DISABLED');
        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans;

            return view('shared.plan_details', compact(['listQuotePlans', 'quoteId', 'planId', 'isPlanUpdateActive']));
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
                'listQuotePlanBenefitsPolicyDetailLink', 'modelName', 'listQuotePlansMembers',
                'listQuotePlanBenefitstravelInconvenienceCover', 'listQuotePlanBenefitsemergencyMedicalCover'
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
                    $listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
                    $listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
                    $listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
                    $listQuotePlanBenefitsCoInsurance = $listQuotePlan->benefits->coInsurance;
                    $listQuotePlanBenefitsRegionCover = $listQuotePlan->benefits->regionCover;
                    $listQuotePlanBenefitsMaternityCover = $listQuotePlan->benefits->maternityCover;
                    $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;

                    foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail) {
                        $listQuotePlanBenefitsPolicyDetailLink = $listQuotePlanBenefitsPolicyDetail->link;
                    }
                }
            }
            $modelName = quoteTypeCode::Health;
            return view('shared.plan_details', compact([
                'listQuotePlanName', 'providerCode', 'providerName',
                'actualPremium', 'discountPremium', 'listQuotePlanBenefitsInclusions',
                'listQuotePlanBenefitsExclusions', 'listQuotePlanBenefitsFeatures',
                'listQuotePlanBenefitsPolicyDetailLink', 'modelName',
                'listQuotePlanBenefitsCoInsurance', 'listQuotePlanBenefitsRegionCover', 'listQuotePlanBenefitsMaternityCover'
            ]));
        }
    }

    public function wcuAssign(Request $request)
    {
        $result = $this->healthQuoteService->assignWCU($request);
        if(count($result) > 0){
            $msg = '';
            foreach ($result as $item){
                $msg = $msg . 'Lead with CDBID ' . $item['leadId'] .' is not assigned. <span style="color:black;">Reason : '. $item['msg'] . '</span> <br>';
            }
            Log::warning('WCU Assignment Failed for '.$request->modelType.' Quote , selected id was '. $request->selectTmLeadId);
            return Redirect::back()->with('message', $msg);
        }
        $assignedUserName = $this->userService->getUserNameById((int)$request->assigned_to_id_new);
        return Redirect::back()->with('success', $request->modelType . ' Leads has been Assigned To ' . $assignedUserName);
    }



    public function manualLeadAssign(Request $request)
    {

        $isValidRequest  = $this->crudService->validateRequest($request->modelType, $request);
        if($isValidRequest != 'true'){
            return redirect()->back()->with('message', $isValidRequest);
        }
        $assignmentResult = $this->{strtolower($request->modelType) . 'QuoteService'}->processManualLeadAssignment($request);
        if(count($assignmentResult) > 0){
            $msg = '';
            foreach($assignmentResult as $assignmentResultItem){
                $msg = $msg . ' Lead with CDBID' . $assignmentResultItem['leadId'] .' is not assigned, Reason : '. $assignmentResultItem['msg'] . ' <br>';
            }
            Log::warning('Manual Lead Assignment Failed for '.$request->modelType.' Quote , selected id was '. $request->selectTmLeadId);
            return Redirect::back()->with('message', $msg);
        }else{
            $assignedUserName = $this->userService->getUserNameById((int)$request->assigned_to_id_new);
            return Redirect::back()->with('success', $request->modelType . ' Leads has been Assigned To ' . $assignedUserName);
        }
    }

    public function addCarQuotePlan(Request $request)
    {
        $quoteUuId = $request->quoteUuId;
        $insuranceproviders = $this->lookupService->getInsuranceProviders();
        $listQuotePlans = $this->carQuoteService->getPlans($quoteUuId);

        return view('components.car-quote-add-plan', compact('quoteUuId', 'insuranceproviders', 'listQuotePlans'));
    }

    public function healthTeamAssign(Request $request)
    {
        $selectedTeam = $request->get('assign_team');

        $lead = $this->healthQuoteService->getEntityPlain($request->get('entityId'));

        $isAssigned = $this->healthQuoteService->assignHealthTeam($request, $lead);

        if(Auth::user()->isHealthWCUAdvisor() && $lead->quote_status_id == QuoteStatusEnum::Qualified && $selectedTeam != quoteTypeCode::GM && $isAssigned){
            return redirect()->to('/quotes/health')->with('success', ' Lead Team has been assigned successfully');
        }

        if($selectedTeam == quoteTypeCode::GM && $isAssigned){
            return redirect()->to('/quotes/health')->with('success', ' Lead has been Converted And Assigned To Group Medical Team');
        }
        if($selectedTeam != quoteTypeCode::GM && $isAssigned){

            return redirect()->to('/quotes/health/' . $lead->uuid)->with('success', ' Lead has been Assigned To ' . strtoupper($selectedTeam) . ' Team');
        }
    }

    public function UpdateLeadStatus(Request $request)
    {
        if(strtolower($request->modelType) == strtolower(quoteTypeCode::Health)) {
            $lead = $this->healthQuoteService->getEntityPlain($request->get('leadId'));
            if (($lead->health_team_type == null  || $lead->health_team_type == quoteTypeCode::WCU )  && $request->leadStatus == QuoteStatusEnum::Qualified) {
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
        $entity = $this->crudService->updateQuoteStatus($request);
        if($entity->health_team_type != null && $entity->quote_status_id == QuoteStatusEnum::Qualified){
            return redirect()->to('/quotes/health')->with('success', ' Lead status has been updated successfully');
        }
        return redirect()->to('/quotes/' . strtolower($request->modelType) . '/' . $entity->uuid)->with('success', ' Lead Status has been Updated');
    }

    public function CarPlanManualProcess(Request $request)
    {
        $response = $this->carQuoteService->carPlanModify($request);

        if ($response == 200 || $response == 201) {
            return redirect()->back()->with('success', 'Car Plan has been saved');
        } else {
            return redirect()->back()->with('message', $response);
        }
    }

    public function loadMoreRecords(Request $request)
    {

        if ($request->has('modelType') && $request->modelType && $request->status) {
            $results = getDataAgainstEveryStatus($request->modelType, $request);

            $html = '';
            if ($results) {
                foreach ($results['leads_list'] as $result) {
                    $html .= ' <li data-block-id="53" class="drag-item">
                    <div class="lead-block rotten">
                        <div class="lead-title">' . $result->code . '</div>
                        <span class="float-right">
                        <a target="_blank" href="/quotes/' . strtolower($request->modelType) . '/' . $result->uuid . '"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                        </span>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                        ' . $result->first_name . ' ' . $result->last_name . '
                        </div>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-building font-1" aria-hidden="true"></i>
                        ' . $result->company_name . '
                        </div>
                        <div class="pad-5"></div>
                        <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp;' . $result->premium . '
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

    public function searchLead(Request $request)
    {

        if ($request->has('modelType') && $request->modelType && $request->term && $request->status) {
            $results = getDataAgainstSearchTerm($request->modelType, $request);

            $html = '';
            if ($results) {
                foreach ($results['leads_list'] as $result) {
                    $html .= ' <li data-block-id="53" class="drag-item">
                    <div class="lead-block rotten">
                        <div class="lead-title">' . $result->code . '</div>
                        <span class="float-right">
                        <a target="_blank" href="/quotes/' . strtolower($request->modelType) . '/' . $result->uuid . '"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                        </span>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                        ' . $result->first_name . ' ' . $result->last_name . '
                        </div>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-building font-1" aria-hidden="true"></i>
                        ' . $result->company_name . '
                        </div>
                        <div class="pad-5"></div>
                        <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp;' . $result->premium . '
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
        return redirect()->to('/quotes/' . strtolower($request->parentType) . '/' . $request->entityUId)->with('success', ' Lead has been Duplicated');
    }

    public function createActivity(Request $request)
    {
        $record = $this->{strtolower($request->modelType) . 'QuoteService'}->getEntityPlain($request->entityId);
        $this->activityService->createActivity($request, $record);
        if (isset($request->isActivityView)) {
            return redirect()->to('/activities/')->with('success', ' Activity has been Created');
        }
        return redirect()->to('/quotes/' . strtolower($request->parentType) . '/' . $request->entityUId)->with('success', ' Activity has been Created');
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

        if($response == 201) {
            $noteId = $this->notesForCustomerService->addCustomerNote($request);
        } else {
            return redirect()->back()->with('message', $response);
        }

        if($noteId) {
            return redirect()->back()->with('success', 'Notes to customer has been sent.');
        }
    }

    public function sendNotesToCustomer(Request $request)
    {
        return $this->notesForCustomerService->notesSendToCustomer($request);
    }

    public function updateQuotePolicy(Request $request)
    {
        $quote = $this->travelQuoteService->updateQuotePolicy($request);

        if($quote) {
            return redirect()->back()->with('success', 'Quote Policy Detail has been updated.');
        }
    }

    public function manualPlanToggle(Request $request) {
        $quote = $this->carQuoteService->updateManualPlansBulk($request);
    }
}
