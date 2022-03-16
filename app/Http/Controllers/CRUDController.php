<?php

namespace App\Http\Controllers;

use App\Enums\HealthTeamType;
use App\Enums\LeadStatusCode;
use App\Enums\quoteTypeCode;
use App\Models\CarQuoteAdvisorToOE;
use App\Models\GenericModel;
use App\Models\InsuranceProvider;
use App\Services\BusinessQuoteService;
use Illuminate\Http\Request;
use App\Services\DropdownSourceService;
use App\Services\HealthQuoteService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\HomeQuoteService;
use App\Services\LeadStatusService;
use App\Services\LifeQuoteService;
use App\Services\TeamService;
use App\Services\TravelQuoteService;
use App\Services\UserService;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Config;
use DB;

class CRUDController extends Controller
{
    protected $genericModel;
    protected $healthQuoteService;
    protected $teamsService;
    protected $dropdownSourceService;
    protected $carQuoteService;
    protected $crudService;
    protected $leadStatusService;
    protected $travelQuoteService;
    protected $lifeQuoteService;
    protected $homeQuoteService;
    protected $businessQuoteService;
    protected $userService;
    public function __construct(
        HealthQuoteService $healthService,
        TeamService $teamsService,
        CRUDService $crudService,
        DropdownSourceService $dropdownSourceService,
        CarQuoteService $carQuoteService,
        LeadStatusService $leadStatusService,
        TravelQuoteService $travelQuoteService,
        LifeQuoteService $lifeQuoteService,
        HomeQuoteService $homeQuoteService,
        BusinessQuoteService $businessQuoteService,
        UserService $userService,
        Request $request
    ) {
        $this->genericModel = new GenericModel();
        $this->healthQuoteService = $healthService;
        $this->teamsService = $teamsService;
        $this->crudService = $crudService;
        $this->dropdownSourceService = $dropdownSourceService;
        $this->carQuoteService = $carQuoteService;
        $this->leadStatusService = $leadStatusService;
        $this->travelQuoteService = $travelQuoteService;
        $this->lifeQuoteService = $lifeQuoteService;
        $this->homeQuoteService = $homeQuoteService;
        $this->businessQuoteService = $businessQuoteService;
        $this->userService = $userService;
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
        //Checking if the loggedIn user is Renewal User
        $isRenewalUser = Auth::user()->isRenewalUser();
        if ($isRenewalUser && strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Car)) {
            $this->crudService->fillRenewalData($this->genericModel);
            $renewalAdvisors = $this->crudService->getRenewalAdvisorsByModelType($this->genericModel->modelType);
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
            return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy', 'isRenewalUser', 'renewalAdvisors'));
        }
        return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy', 'isRenewalUser', 'renewalAdvisors'));
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
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        if (!$record) abort(404);
        $model = $this->genericModel;
        $customTitles = $customTableList = [];
        $leadStatuses = DB::table('quote_status')
            ->select('id', 'text')
            ->whereNotIn('text', [
                'AML Screening Cleared', 'Draft', 'Cancelled', 'AML Screening Failed', 'Transaction Declined', 'Policy Issued', 'Policy Invoiced',
                'Completed', 'Pending', 'Rejected', 'Issued', 'Approved', 'Approval required', 'Resubmit for approval'
            ])->orderBy('sort_order', 'asc')->get();
        $lostReasons = DB::table('lost_reasons')
            ->select('id', 'text')
            ->get();
        $selectedLostReasonId = '';
        if (strtolower($this->genericModel->modelType) != 'teams' && strtolower($this->genericModel->modelType) != 'leadstatus') {
            $selectedLostReasonId = $this->crudService->getSelectedLostReason($this->genericModel->modelType, $record->id);
        }

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
        $quoteTypes = 'Health,Car,Travel,Life,Home,Business';
        $serviceType = str_contains($quoteTypes, ucwords($model->modelType)) ? strtolower($model->modelType) . 'QuoteService' : lcfirst(ucwords($model->modelType)) . 'Service';

        if ($this->genericModel->modelType == "Car") { // Car plans to display on detail view

            $listQuotePlans = '';
            $quotePlans = $this->carQuoteService->getQuotePlans($id);
            $carQuotePlanAddons = $this->carQuoteService->getCarQuotePlanAddons($id);
            $ecomCarInsuranceQuoteUrl = Config::get('constants.ECOM_CAR_INSURANCE_QUOTE_URL');
            $vehicleTypeText = $this->carQuoteService->getCarQuoteVehicleType($id);

            if (isset($quotePlans->message) && $quotePlans->message != '') {
                $listQuotePlans = $quotePlans->message;
            } else {
                if (gettype($quotePlans) != 'string') {
                    $listQuotePlans = $quotePlans->quotes->plans;
                    $listQuote = $quotePlans->quotes;
                } else {
                    $listQuotePlans = $quotePlans;
                    $listQuote = null;
                }
            }

            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'listQuotePlans', 'customTableList',
                'ecomCarInsuranceQuoteUrl', 'carQuotePlanAddons', 'vehicleTypeText', 'leadStatuses',
                'lostReasons', 'selectedLostReasonId', 'listQuote'
            ]));
        } else if ($this->genericModel->modelType == quoteTypeCode::Travel) { // Travel plans to display on detail view
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

            // $members_detail = $this->travelQuoteService->getMembersDetail($record->id);
            $members_detail = [];

            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'listQuotePlans', 'customTableList',
                'leadStatuses', 'lostReasons', 'selectedLostReasonId', 'members_detail'
            ]));
        } else {
            return view('shared.show', compact(['record', 'model', 'customTitles', 'customTableList', 'advisors', 'leadStatuses', 'lostReasons', 'selectedLostReasonId']));
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
            foreach ($modelPropertiesList as $property => $value) {
                if (strpos($value, 'required') && $property != 'id' && $property != 'code' && $property != 'email' && $property != 'mobile_no' && !strpos($modelSkipPropertiesList, $property)) {
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
    }

    private function fillModelByModelType($type, Request $request)
    {
        $modelType = json_decode($request->get('modelType'), true) ?? $type;
        if ($modelType == null) $modelType = $request->get('modelType');
        $quoteTypes = 'Health,Car,Travel,Life,Home,Business';
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

        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans;

            return view('shared.plan_details', compact(['listQuotePlans', 'quoteId', 'planId']));
        }
    }

    public function travel_plan_details($quoteId, $planId)
    {
        $quotePlans = $this->travelQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans;
            $listQuotePlansMembers = $quotePlans->quotes->members;
            foreach ($listQuotePlans as $listQuotePlan) { // Main

                if ($listQuotePlan->id == $planId) {
                    $listQuotePlanName = $listQuotePlan->name;
                    $providerCode = $listQuotePlan->providerCode;
                    $providerName = $listQuotePlan->providerName;
                    $travelType = $listQuotePlan->travelType;
                    $actualPremium = $listQuotePlan->actualPremium;
                    $discountPremium = $listQuotePlan->discountPremium;
                    $listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
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
                'listQuotePlanBenefitsPolicyDetailLink', 'modelName', 'listQuotePlansMembers'
            ]));
        }
    }

    public function manualLeadAssign(Request $request)
    {
        $assignedToUserIdNew = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId;
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        if ($assignedToUserIdNew == '' || $assignedToUserIdNew == null) {
            return redirect()->back()->with('message', 'Please select user to assign leads');
        }
        if ($leadsIds == '' || $leadsIds == null) {
            return redirect()->back()->with('message', 'Please select lead(s) to assign');
        }
        foreach ($leadsIds as $tmLeadsId) {
            $userId = (int)$assignedToUserIdNew;
            $entity = $this->{strtolower($request->modelType) . 'QuoteService'}->getEntityPlain($tmLeadsId);
            if ($entity) {
                if (Auth::user()->hasRole('WCU_ADVISOR')) {
                    $entity->wcu_id = $userId;
                } else {
                    $entity->advisor_id = $userId;
                }
                $advisorOE = CarQuoteAdvisorToOE::where('advisor_id', $userId)->first();
                if (!empty($advisorOE) && strtolower($request->modelType) == 'car') {
                    $entity->oe_id = $advisorOE->oe_id;
                }
                $entity->save();
                $this->{strtolower($request->modelType) . 'QuoteService'}->updateChildRecord($tmLeadsId);
            } else {
                return redirect()->back()->with('message', 'Invalid lead selected for assignment');
            }
        }
        $assignedUserName = $this->userService->getUserNameById($assignedToUserIdNew);
        return Redirect::back()->with('success', $request->modelType . ' Leads has been Assigned To ' . $assignedUserName);
    }

    public function manualLeadAssignAfterTeamAssign(Request $request)
    {
        $assignedToUserIdNew = $request->assigned_to_id_new;
        $leadsIds = $request->entityId;
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        foreach ($leadsIds as $tmLeadsId) {
            $entity = $this->{strtolower($request->modelType) . 'QuoteService'}->getEntityPlain($tmLeadsId);
            if ($entity) {
                $userId = (int)$assignedToUserIdNew;
                $entity->advisor_id = $userId;
                $advisorOE = CarQuoteAdvisorToOE::where('advisor_id', $userId)->first();
                if (!empty($advisorOE) && strtolower($request->modelType) == 'car') {
                    $entity->oe_id = $advisorOE->oe_id;
                }
                $entity->save();
            } else {
                return redirect()->back()->with('message', 'Invalid lead selected for assignment');
            }
        }
        $this->{strtolower($request->modelType) . 'QuoteService'}->updateChildRecord($tmLeadsId);
        $assignedUserName = $this->userService->getUserNameById($assignedToUserIdNew);
        return Redirect::back()->with('success', ' Lead has been Assigned To ' . $assignedUserName);
    }

    public function addCarQuotePlan(Request $request)
    {
        $quoteUuId = $request->quoteUuId;
        $insuranceproviders = InsuranceProvider::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        return view('shared.add_quote', compact('insuranceproviders', 'quoteUuId'));
    }

    public function healthTeamAssign(Request $request)
    {
        $selectedTeam = $request->get('assign_team');
        $lead = $this->healthQuoteService->getEntityPlain($request->get('entityId'));
        $lead->health_team_type = $request->get('assign_team');
        $lead->save();
        if ($selectedTeam == 'GM') {
            $this->healthQuoteService->convertLeadToGM($lead);
            return redirect()->to('/quotes/health')->with('success', ' Lead has been Converted And Assigned To Group Medical Team');
        } else {
            return redirect()->to('/quotes/health/' . $lead->uuid)->with('success', ' Lead has been Assigned To ' . strtoupper($selectedTeam) . ' Team');
        }
    }

    public function UpdateLeadStatus(Request $request)
    {
        $transctionApprovedId = DB::table('quote_status')->where('text', LeadStatusCode::TRANSACTION_APPROVED)->value('id');
        $lostId = DB::table('quote_status')->where('text', LeadStatusCode::LOST)->value('id');
        if ($request->leadStatus == $lostId) {
            $this->validate($request, [
                'lostReason' => 'required',
            ]);
        }
        if ($request->leadStatus == $transctionApprovedId) {
            $this->validate($request, [
                'trans_code' => 'required',
            ]);
        }
        $entity = $this->crudService->updateQuoteStatus($request);
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

    public function loadMoreRecords(Request $request) {

        if($request->has('modelType') && $request->modelType && $request->status)
        {
            $results = getDataAgainstEveryStatus($request->modelType, $request);

            $html = '';
            if($results) {
                foreach($results['leads_list'] as $result) {
                    $html .=' <li data-block-id="53" class="drag-item">
                    <div class="lead-block rotten">
                        <div class="lead-title">'.$result->code.'</div>
                        <span class="float-right">
                            <a href="#" planDetailUrl="'.$result->id.'/lead_details?modelType='.$request->modelType.'" data-toggle="modal" data-target="#quoteModal" class="quotePlanModalPopup"><i class="fa fa-pencil" aria-hidden="true"></i></a></span>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                        '.$result->first_name.' '.$result->last_name.'
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

    public function searchLead(Request $request) {

        if($request->has('modelType') && $request->modelType && $request->term && $request->status)
        {
            $results = getDataAgainstSearchTerm($request->modelType, $request->term, $request->status);

            $html = '';
            if($results) {
                foreach($results['leads_list'] as $result) {
                    $html .=' <li data-block-id="53" class="drag-item">
                    <div class="lead-block rotten">
                        <div class="lead-title">'.$result->code.'</div>
                        <span class="float-right">
                            <a href="#" planDetailUrl="'.$result->id.'/lead_details?modelType='.$request->modelType.'" data-toggle="modal" data-target="#quoteModal" class="quotePlanModalPopup"><i class="fa fa-pencil" aria-hidden="true"></i></a></span>
                        <div class="pad-5"></div>
                        <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                        '.$result->first_name.' '.$result->last_name.'
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
}
