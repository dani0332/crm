<?php

namespace App\Http\Controllers;

use App\Enums\HealthTeamType;
use App\Enums\LeadStatusCode;
use App\Enums\quoteTypeCode;
use App\Models\CarQuoteAdvisorToOE;
use App\Models\GenericModel;
use App\Models\InsuranceProvider;
use App\Models\VehicleType;
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
        $gridData = $this->crudService->getGridData($this->genericModel, $request);
        $advisors = $this->crudService->getAdvisorsByModelType($this->genericModel->modelType);
        $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
        $dropdownSource = $customTitles = [];
        foreach ($this->genericModel->properties as $property => $value) {
            if (str_contains($value, 'title')) {
                $customTitles[$property] = $this->crudService->getCustomTitleByModelType($this->genericModel->modelType, $property);
            }
            if (str_contains($value, 'select')) {
                $dropdownValue = $this->dropdownSourceService->getDropdownSource($property);
                $dropdownSource[$property] = $dropdownValue;
            }
        }
        $model = $this->genericModel;
        if ($request->ajax()) {
            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
            return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy'));
        }
        return view('shared.view', compact('model', 'dropdownSource', 'customTitles', 'advisors', 'isManagerORDeputy'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
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
        return view('shared.add', compact('model', 'dropdownSource', 'customTitles'));
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
    public function show($id)
    {
        $record = $this->crudService->getEntity($this->genericModel->modelType, $id);
        if (!$record) abort(404);
        $model = $this->genericModel;
        $customTitles = $customTableList = [];
        $leadStatuses = DB::table('quote_status')
            ->select('id', 'text')
            ->whereNotIn('text', [
                'AML Screening Cleared', 'AML Screening Failed', 'Transaction Declined', 'Policy Issued', 'Policy Invoiced',
                'Completed', 'Pending', 'Rejected', 'Issued', 'Approved', 'Approval required', 'Resubmit for approval'
            ])
            ->get();
        $lostReasons = DB::table('lost_reasons')
            ->select('id', 'text')
            ->get();
        $selectedLostReasonId = $this->crudService->getSelectedLostReason($this->genericModel->modelType, $record->id);
        if (strtolower($this->genericModel->modelType) == strtolower(quoteTypeCode::Health) && ($record->health_team_type == HealthTeamType::EBP || $record->health_team_type == HealthTeamType::RM)) {
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
                } else {
                    $listQuotePlans = $quotePlans;
                }
            }

            return view('shared.show', compact([
                'record', 'model', 'customTitles', 'listQuotePlans', 'customTableList',
                'ecomCarInsuranceQuoteUrl', 'carQuotePlanAddons', 'vehicleTypeText', 'leadStatuses',
                'lostReasons', 'selectedLostReasonId'
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
        return view('shared.edit', compact(['record', 'model', 'dropdownSource', 'customTitles', 'customLists']));
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

    public function plan_details($quoteId, $planId)
    {
        $quotePlans = $this->carQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans;
            foreach ($listQuotePlans as $listQuotePlan) { // Main

                if ($listQuotePlan->id == $planId) {
                    $listQuotePlanName = $listQuotePlan->name;
                    $providerCode = $listQuotePlan->providerCode;
                    $providerName = $listQuotePlan->providerName;
                    $repairType = $listQuotePlan->repairType;
                    $actualPremium = $listQuotePlan->actualPremium;
                    $discountPremium = $listQuotePlan->discountPremium;
                    $listQuotePlanAddonss = $listQuotePlan->addons;
                    $listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
                    $listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
                    $listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
                    $listQuotePlanBenefitsRsas = $listQuotePlan->benefits->roadSideAssistance;
                    $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;

                    foreach ($listQuotePlanAddonss as $listQuotePlanAddon) {
                        $listQuotePlanAddons[] = $listQuotePlanAddon; // Get Addons Names

                        foreach ($listQuotePlanAddon->carAddonOption as $listQuotePlanAddonsOptions) {
                            $listQuotePlanAddonValues[] = $listQuotePlanAddonsOptions->value;
                            $listQuotePlanAddonPrices[] = $listQuotePlanAddonsOptions->price;
                        }
                    }
                    foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail) {
                        $listQuotePlanBenefitsPolicyDetailLink = $listQuotePlanBenefitsPolicyDetail->link;
                    }
                }
            }
            return view('shared.plan_details', compact([
                'listQuotePlanName', 'providerCode', 'providerName', 'repairType',
                'actualPremium', 'discountPremium', 'listQuotePlanAddons', 'listQuotePlanAddonValues', 'listQuotePlanBenefitsInclusions',
                'listQuotePlanBenefitsExclusions', 'listQuotePlanBenefitsFeatures', 'listQuotePlanBenefitsRsas',
                'listQuotePlanBenefitsPolicyDetailLink', 'listQuotePlanAddonPrices'
            ]));
        }
    }

    public function manualLeadAssign(Request $request)
    {
        $assignedToUserIdNew = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId;
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        foreach ($leadsIds as $tmLeadsId) {
            $entity = $this->{strtolower($request->modelType) . 'QuoteService'}->getEntityPlain($tmLeadsId);
            $userId = (int)$assignedToUserIdNew;
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
            $userId = (int)$assignedToUserIdNew;
            $entity->advisor_id = $userId;
            $advisorOE = CarQuoteAdvisorToOE::where('advisor_id', $userId)->first();
            if (!empty($advisorOE) && strtolower($request->modelType) == 'car') {
                $entity->oe_id = $advisorOE->oe_id;
            }
            $entity->save();
        }
        $assignedUserName = $this->userService->getUserNameById($assignedToUserIdNew);
        return Redirect::back()->with('success', ' Lead has been Assigned To ' . $assignedUserName);
    }

    public function add_quote(Request $request)
    {
        $insuranceproviders = InsuranceProvider::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        return view('shared.add_quote', compact('insuranceproviders'));
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
}
